<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

use DateTimeImmutable;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use ErrorException;
use Exception;
use Laminas\Stdlib\ErrorHandler;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function chmod;
use function dirname;
use function error_log;
use function fclose;
use function file_exists;
use function filemtime;
use function fopen;
use function fwrite;
use function get_resource_type;
use function gettype;
use function is_array;
use function is_dir;
use function is_link;
use function is_numeric;
use function is_resource;
use function is_string;
use function is_writable;
use function iterator_to_array;
use function pathinfo;
use function preg_grep;
use function preg_match_all;
use function scandir;
use function sprintf;
use function str_replace;
use function stream_get_meta_data;
use function time;
use function touch;
use function unlink;

use const PATHINFO_DIRNAME;
use const PHP_EOL;

class Stream extends AbstractWriter
{
    private const DEFAULT_PERMISSIONS = 0666;

    /**
     * Separator between log entries
     */
    protected string $logSeparator = PHP_EOL;

    /**
     * Holds the PHP stream to log to.
     */
    protected mixed $stream;

    protected ?int $logLifetime = null;

    protected ?string $streamFormat = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws ErrorException
     */
    public function __construct(
        mixed $streamOrUrl,
        ?string $mode = null,
        ?string $logSeparator = null,
        ?int $filePermissions = self::DEFAULT_PERMISSIONS,
    ) {
        $logLifetime  = null;
        $streamFormat = null;

        if ($streamOrUrl instanceof Traversable) {
            $streamOrUrl = iterator_to_array($streamOrUrl);
        }
        if (is_array($streamOrUrl)) {
            parent::__construct($streamOrUrl);
            $logLifetime     = $streamOrUrl['log_lifetime'] ?? null;
            $streamFormat    = $streamOrUrl['stream_format'] ?? null;
            $mode            = $streamOrUrl['mode'] ?? null;
            $logSeparator    = $streamOrUrl['log_separator'] ?? null;
            $filePermissions = $streamOrUrl['chmod'] ?? $filePermissions;
            $streamOrUrl     = $streamOrUrl['stream'] ?? null;
        }

        // Setting the default mode
        if (null === $mode) {
            $mode = 'a';
        }

        if (! is_string($streamOrUrl) && ! is_resource($streamOrUrl)) {
            throw new InvalidArgumentException(sprintf(
                'Resource is not a stream nor a string; received "%s"',
                gettype($streamOrUrl)
            ));
        }

        $error = null;
        if (is_resource($streamOrUrl)) {
            if ('stream' !== get_resource_type($streamOrUrl)) {
                throw new InvalidArgumentException(sprintf(
                    'Resource is not a stream; received "%s',
                    get_resource_type($streamOrUrl)
                ));
            }

            if ('a' !== $mode) {
                throw new InvalidArgumentException(sprintf(
                    'Mode must be "a" on existing streams; received "%s"',
                    $mode
                ));
            }

            $this->stream = $streamOrUrl;
        } else {
            ErrorHandler::start();
            if (isset($filePermissions) && ! file_exists($streamOrUrl) && is_writable(dirname($streamOrUrl))) {
                touch($streamOrUrl);
                chmod($streamOrUrl, $filePermissions);
            }
            $this->stream = fopen($streamOrUrl, $mode);
            $error        = ErrorHandler::stop();
        }

        if (! $this->stream) {
            throw new RuntimeException(sprintf(
                '"%s" cannot be opened with mode "%s"',
                $streamOrUrl,
                $mode
            ), 0, $error);
        }

        if (null !== $logSeparator) {
            $this->setLogSeparator($logSeparator);
        }

        if (is_numeric($logLifetime)) {
            $logLifetime = ($logLifetime > 0 ? '-' . $logLifetime : $logLifetime) . ' days';

            try {
                $logLifetime = (new DateTimeImmutable($logLifetime))->getTimestamp();
                if ($logLifetime >= time()) {
                    $logLifetime = null;
                }
            } catch (Exception $e) {
                $logLifetime = null;
            }

            if ($logLifetime !== null && $streamFormat !== null) {
                $this->streamFormat = $streamFormat;
            }

            $this->logLifetime = $logLifetime;
        }
    }

    /**
     * Write a message to the log.
     */
    protected function doWrite(array $event): void
    {
        $line = $this->formatter->format($event) . $this->logSeparator;
        fwrite($this->stream, $line);
    }

    /**
     * Set log separator string
     */
    public function setLogSeparator(string $logSeparator): static
    {
        $this->logSeparator = $logSeparator;
        return $this;
    }

    /**
     * Get log separator string
     */
    public function getLogSeparator(): string
    {
        return $this->logSeparator;
    }

    /**
     * Close the stream resource.
     */
    public function shutdown(): void
    {
        if ($this->logLifetime !== null && $this->streamFormat !== null) {
            $this->removeOldLogs();
        }

        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    protected function removeOldLogs(): void
    {
        preg_match_all('/{([a-z])}/i', $this->streamFormat, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $this->streamFormat = str_replace('{' . $match . '}', '\d+', $this->streamFormat);
            }

            $this->streamFormat = str_replace('.', '\.', $this->streamFormat);
        }
        $streamData = stream_get_meta_data($this->stream);

        $path      = $streamData['uri'];
        $directory = pathinfo($path, PATHINFO_DIRNAME);

        if (! is_dir($directory)) {
            error_log("{$directory} is not a directory");
            return;
        }

        $files   = scandir($directory) ?: [];
        $matches = preg_grep('/^' . $this->streamFormat . '$/', $files) ?: [];

        foreach ($matches as $match) {
            $match = sprintf('%s/%s', $directory, $match);

            if (is_link($match)) {
                continue;
            }

            $fileTimestamp = filemtime($match);

            if (
                $fileTimestamp !== false
                && $fileTimestamp < $this->logLifetime
                && is_writable($directory)
            ) {
                if (! @unlink($match)) {
                    error_log("{$match} could not be deleted");
                }
            }
        }
    }
}
