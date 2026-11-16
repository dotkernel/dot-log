<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

use DateTimeImmutable;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Formatter\Simple;
use Exception;
use Laminas\Stdlib\ErrorHandler;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function array_map;
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
use function implode;
use function is_array;
use function is_dir;
use function is_int;
use function is_link;
use function is_numeric;
use function is_resource;
use function is_string;
use function is_writable;
use function iterator_to_array;
use function pathinfo;
use function preg_grep;
use function preg_match;
use function preg_quote;
use function preg_split;
use function scandir;
use function sprintf;
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
     * Date placeholders in a stream name, for example {Y}
     */
    public const PLACEHOLDER_PATTERN = '/\{[a-z]\}/i';

    /**
     * Separator between log entries
     */
    protected string $logSeparator = PHP_EOL;

    /**
     * Holds the PHP stream to log to.
     *
     * @var resource|closed-resource
     */
    protected mixed $stream;

    protected ?int $logLifetime = null;

    protected ?string $streamFormat = null;

    /**
     * @param string|resource|iterable<string, mixed> $streamOrUrl a path or URL, a stream resource, or options:
     *     stream, mode, log_separator, chmod, log_lifetime, stream_format, filters, formatter,
     *     filter_manager, formatter_manager
     * @throws ContainerExceptionInterface
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

        if (! $this->hasFormatter()) {
            $this->setFormatter(new Simple());
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
        } elseif (is_string($streamOrUrl)) {
            ErrorHandler::start();
            if (is_int($filePermissions) && ! file_exists($streamOrUrl) && is_writable(dirname($streamOrUrl))) {
                touch($streamOrUrl);
                chmod($streamOrUrl, $filePermissions);
            }
            $stream = fopen($streamOrUrl, $mode);
            $error  = ErrorHandler::stop();

            if ($stream === false) {
                throw new RuntimeException(sprintf(
                    '"%s" cannot be opened with mode "%s"',
                    $streamOrUrl,
                    $mode
                ), 0, $error);
            }
            $this->stream = $stream;
        }

        if (is_string($logSeparator)) {
            $this->setLogSeparator($logSeparator);
        }

        if (is_numeric($logLifetime)) {
            $logLifetime = ($logLifetime > 0 ? '-' . $logLifetime : $logLifetime) . ' days';

            try {
                $logLifetime = (new DateTimeImmutable($logLifetime))->getTimestamp();
                if ($logLifetime >= time()) {
                    $logLifetime = null;
                }
            } catch (Exception) {
                $logLifetime = null;
            }

            if ($logLifetime !== null && is_string($streamFormat)) {
                $this->streamFormat = $streamFormat;
            }

            $this->logLifetime = $logLifetime;
        }
    }

    /**
     * Write a message to the log.
     *
     * @param array<string, mixed> $event
     */
    protected function doWrite(array $event): void
    {
        if (! is_resource($this->stream)) {
            throw new RuntimeException('The log stream is closed');
        }

        $formatter = $this->getFormatter() ?? new Simple();
        fwrite($this->stream, $formatter->format($event) . $this->logSeparator);
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

    /**
     * Deletes log files of this writer that are older than the configured lifetime.
     *
     * Runs only when the configured stream contains date placeholders such as {Y}: only then can this writer's
     * files be told apart from other files in the same directory.
     */
    protected function removeOldLogs(): void
    {
        $pattern = $this->getOldLogsPattern();
        if ($pattern === null || ! is_resource($this->stream)) {
            return;
        }

        $path = stream_get_meta_data($this->stream)['uri'] ?? null;
        if (! is_string($path)) {
            return;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        if (! is_dir($directory)) {
            error_log("{$directory} is not a directory");
            return;
        }

        $files   = scandir($directory) ?: [];
        $matches = preg_grep($pattern, $files) ?: [];

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

    /**
     * Builds the pattern matching the file names this writer creates, or null when the stream has no date placeholder.
     *
     * Every placeholder matches letters and digits (date() returns month and day names for some characters); the rest
     * of the file name is matched literally.
     */
    public function getOldLogsPattern(): ?string
    {
        if ($this->streamFormat === null || ! preg_match(self::PLACEHOLDER_PATTERN, $this->streamFormat)) {
            return null;
        }

        $parts = preg_split(self::PLACEHOLDER_PATTERN, $this->streamFormat) ?: [];

        return '/^' . implode(
            '[A-Za-z0-9]+',
            array_map(static fn (string $part): string => preg_quote($part, '/'), $parts)
        ) . '$/';
    }
}
