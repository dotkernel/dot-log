<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use ErrorException;
use Laminas\Stdlib\ErrorHandler;
use Traversable;

use function chmod;
use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function get_resource_type;
use function gettype;
use function is_array;
use function is_resource;
use function is_string;
use function is_writable;
use function iterator_to_array;
use function sprintf;
use function touch;

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

    /**
     * @throws ErrorException
     */
    public function __construct(
        mixed $streamOrUrl,
        ?string $mode = null,
        ?string $logSeparator = null,
        ?int $filePermissions = self::DEFAULT_PERMISSIONS,
    ) {
        if ($streamOrUrl instanceof Traversable) {
            $streamOrUrl = iterator_to_array($streamOrUrl);
        }

        if (is_array($streamOrUrl)) {
            parent::__construct($streamOrUrl);
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
            $this->stream = fopen($streamOrUrl, $mode, false);
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
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
