<?php

declare(strict_types=1);

namespace Dot\Log;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\ProcessorInterface;
use Dot\Log\Writer\WriterInterface;
use ErrorException;
use Exception;
use Laminas\Stdlib\SplPriorityQueue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use Traversable;

use function array_reverse;
use function array_search;
use function class_exists;
use function count;
use function error_get_last;
use function error_reporting;
use function floor;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function preg_match_all;
use function register_shutdown_function;
use function restore_error_handler;
use function restore_exception_handler;
use function set_error_handler;
use function set_exception_handler;
use function sprintf;
use function strtr;
use function var_export;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

class Logger extends AbstractLogger
{
    /**
     * @link http://tools.ietf.org/html/rfc3164
     *
     * @const int defined from the BSD Syslog message severities
     */
    public const EMERG  = 0;
    public const ALERT  = 1;
    public const CRIT   = 2;
    public const ERR    = 3;
    public const WARN   = 4;
    public const NOTICE = 5;
    public const INFO   = 6;
    public const DEBUG  = 7;

    /**
     * Map native PHP errors to level
     *
     * @var array<int, int>
     */
    public static array $errorLevelMap = [
        E_NOTICE            => self::NOTICE,
        E_USER_NOTICE       => self::NOTICE,
        E_WARNING           => self::WARN,
        E_CORE_WARNING      => self::WARN,
        E_USER_WARNING      => self::WARN,
        E_ERROR             => self::ERR,
        E_USER_ERROR        => self::ERR,
        E_CORE_ERROR        => self::ERR,
        E_RECOVERABLE_ERROR => self::ERR,
        E_PARSE             => self::ERR,
        E_COMPILE_ERROR     => self::ERR,
        E_COMPILE_WARNING   => self::ERR,
        E_DEPRECATED        => self::DEBUG,
        E_USER_DEPRECATED   => self::DEBUG,
    ];

    /**
     * Registered error handler
     */
    protected static bool $registeredErrorHandler = false;

    /**
     * Registered shutdown error handler
     */
    protected static bool $registeredFatalErrorShutdownFunction = false;

    /**
     * Registered exception handler
     */
    protected static bool $registeredExceptionHandler = false;

    /**
     * List of PSR-3 level name => level code
     *
     * @var array<string, int>
     */
    protected array $levels = [
        LogLevel::EMERGENCY => self::EMERG,
        LogLevel::ALERT     => self::ALERT,
        LogLevel::CRITICAL  => self::CRIT,
        LogLevel::ERROR     => self::ERR,
        LogLevel::WARNING   => self::WARN,
        LogLevel::NOTICE    => self::NOTICE,
        LogLevel::INFO      => self::INFO,
        LogLevel::DEBUG     => self::DEBUG,
    ];

    /** @var SplPriorityQueue<WriterInterface, int> */
    protected SplPriorityQueue $writers;

    /** @var SplPriorityQueue<ProcessorInterface, int> */
    protected SplPriorityQueue $processors;

    protected ?WriterPluginManager $writerPlugins = null;

    protected ?ProcessorPluginManager $processorPlugins = null;

    /**
     * Constructor
     *
     * Set options for a logger. Accepted options are:
     * - writers: list of writers, each [name, level|priority, options]; level/priority defaults to 1
     * - processors: list of processors, each [name, level|priority, options]; level/priority defaults to 1
     * - writer_plugin_manager / processor_plugin_manager: plugin manager instances
     * - exceptionhandler: if true, register this logger as exception handler
     * - errorhandler: if true, register this logger as error handler
     * - fatal_error_shutdownfunction: if true, register a shutdown function logging fatal errors
     *
     * @param iterable<string, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function __construct(?iterable $options = null)
    {
        $this->writers    = new SplPriorityQueue();
        $this->processors = new SplPriorityQueue();

        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if ($options === null || $options === []) {
            return;
        }

        // Inject writer plugin manager, if available
        if (
            isset($options['writer_plugin_manager'])
            && $options['writer_plugin_manager'] instanceof WriterPluginManager
        ) {
            $this->setWriterPluginManager($options['writer_plugin_manager']);
        }

        // Inject processor plugin manager, if available
        if (
            isset($options['processor_plugin_manager'])
            && $options['processor_plugin_manager'] instanceof ProcessorPluginManager
        ) {
            $this->setProcessorPluginManager($options['processor_plugin_manager']);
        }

        if (isset($options['writers']) && is_array($options['writers'])) {
            foreach ($options['writers'] as $writer) {
                [$name, $level, $writerOptions] = $this->readPluginSpec($writer, 'writer');
                $this->addWriter($name, $level, $writerOptions);
            }
        }

        if (isset($options['processors']) && is_array($options['processors'])) {
            foreach ($options['processors'] as $processor) {
                [$name, $level, $processorOptions] = $this->readPluginSpec($processor, 'processor');
                $this->addProcessor($name, $level, $processorOptions);
            }
        }

        if (isset($options['exceptionhandler']) && $options['exceptionhandler'] === true) {
            static::registerExceptionHandler($this);
        }

        if (isset($options['errorhandler']) && $options['errorhandler'] === true) {
            static::registerErrorHandler($this);
        }

        if (isset($options['fatal_error_shutdownfunction']) && $options['fatal_error_shutdownfunction'] === true) {
            static::registerFatalErrorShutdownFunction($this);
        }
    }

    /**
     * Shutdown all writers. Iterates a copy: iterating an SplPriorityQueue removes its elements.
     */
    public function __destruct()
    {
        foreach ($this->writers->toArray() as $writer) {
            try {
                $writer->shutdown();
            } catch (Exception) {
            }
        }
    }

    public function getWriterPluginManager(): WriterPluginManager
    {
        if (null === $this->writerPlugins) {
            $this->writerPlugins = new WriterPluginManager();
        }
        return $this->writerPlugins;
    }

    public function setWriterPluginManager(WriterPluginManager $writerPlugins): static
    {
        $this->writerPlugins = $writerPlugins;
        return $this;
    }

    /**
     * Get a writer instance
     *
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function writerPlugin(string $name, ?array $options = null): WriterInterface
    {
        return $this->getWriterPluginManager()->build($name, $options);
    }

    /**
     * Add a writer to a logger
     *
     * @param int $level the writer priority: writers with a higher value run first
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function addWriter(WriterInterface|string $writer, int $level = 1, ?array $options = null): static
    {
        if (is_string($writer)) {
            $writer = $this->writerPlugin($writer, $options);
        }

        $this->writers->insert($writer, $level);

        return $this;
    }

    /**
     * @return SplPriorityQueue<WriterInterface, int>
     */
    public function getWriters(): SplPriorityQueue
    {
        return $this->writers;
    }

    /**
     * @param SplPriorityQueue<WriterInterface, int> $writers
     */
    public function setWriters(SplPriorityQueue $writers): static
    {
        foreach ($writers->toArray() as $writer) {
            if (! $writer instanceof WriterInterface) {
                throw new InvalidArgumentException(
                    'Writers must be a SplPriorityQueue of ' . WriterInterface::class
                );
            }
        }
        $this->writers = $writers;
        return $this;
    }

    public function getProcessorPluginManager(): ProcessorPluginManager
    {
        if (null === $this->processorPlugins) {
            $this->processorPlugins = new ProcessorPluginManager();
        }
        return $this->processorPlugins;
    }

    /**
     * @param string|ProcessorPluginManager $plugins a ProcessorPluginManager instance or class name
     */
    public function setProcessorPluginManager(string|ProcessorPluginManager $plugins): static
    {
        if (is_string($plugins) && class_exists($plugins)) {
            $plugins = new $plugins();
        }
        if (! $plugins instanceof ProcessorPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'Processor plugin manager must extend %s; received %s',
                ProcessorPluginManager::class,
                get_debug_type($plugins)
            ));
        }

        $this->processorPlugins = $plugins;
        return $this;
    }

    /**
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function processorPlugin(string $name, ?array $options = null): ProcessorInterface
    {
        return $this->getProcessorPluginManager()->build($name, $options);
    }

    /**
     * @param int $level the processor priority: processors with a higher value run first
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function addProcessor(
        ProcessorInterface|string $processor,
        int $level = 1,
        ?array $options = null
    ): static {
        if (is_string($processor)) {
            $processor = $this->processorPlugin($processor, $options);
        }

        $this->processors->insert($processor, $level);

        return $this;
    }

    /**
     * @return SplPriorityQueue<ProcessorInterface, int>
     */
    public function getProcessors(): SplPriorityQueue
    {
        return $this->processors;
    }

    /**
     * @param int|string $level a level code (0 to 7) or a PSR-3 level name
     * @param iterable<string, mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, iterable $context = []): void
    {
        $level = $this->normalizeLevel($level);

        if ($context instanceof Traversable) {
            $context = iterator_to_array($context);
        }

        if ($this->writers->count() === 0) {
            throw new RuntimeException('No log writer specified');
        }

        $event = [
            'timestamp' => new DateTimeImmutable(),
            'level'     => $level,
            'levelName' => array_search($level, $this->levels, true),
            'message'   => $this->handlePlaceholders((string) $message, $context),
            'context'   => $context,
        ];

        foreach ($this->processors->toArray() as $processor) {
            $event = $processor->process($event);
        }

        foreach ($this->writers->toArray() as $writer) {
            $writer->write($event);
        }
    }

    /**
     * Replaces {placeholder} with the matching context value. Null and missing values are left in place.
     *
     * @param array<array-key, mixed> $context
     */
    protected function handlePlaceholders(string $message, array $context): string
    {
        if (! preg_match_all('/\{([\w.]+)}/', $message, $matches)) {
            return $message;
        }

        $replacements = [];
        foreach ($matches[1] as $match) {
            if (! isset($context[$match])) {
                continue;
            }

            $replacements['{' . $match . '}'] = self::placeholderValue($context[$match]);
        }

        return strtr($message, $replacements);
    }

    /**
     * String representation of a context value used in a placeholder.
     */
    public static function placeholderValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value), $value instanceof Stringable => (string) $value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::RFC3339),
            is_object($value) => '[object ' . $value::class . ']',
            default => '[' . get_debug_type($value) . ']',
        };
    }

    protected function normalizeLevel(mixed $level): int
    {
        if (is_string($level) && isset($this->levels[$level])) {
            $level = $this->levels[$level];
        } elseif (is_numeric($level) && floor((float) $level) === (float) $level) {
            $level = (int) $level;
        }

        if (! is_int($level) || $level < 0 || $level >= count($this->levels)) {
            throw new InvalidArgumentException(sprintf(
                '$level must be an integer >= 0 and < %d; received %s',
                count($this->levels),
                var_export($level, true)
            ));
        }

        return $level;
    }

    /**
     * Reads [name, level|priority, options] of a writer or processor from the logger options.
     *
     * @return array{0: string, 1: int, 2: array<array-key, mixed>|null}
     */
    private function readPluginSpec(mixed $spec, string $type): array
    {
        if ($spec instanceof Traversable) {
            $spec = iterator_to_array($spec);
        }

        if (! is_array($spec) || ! isset($spec['name']) || ! is_string($spec['name'])) {
            throw new InvalidArgumentException('Options must contain a name for the ' . $type);
        }

        $level = $spec['level'] ?? $spec['priority'] ?? 1;
        if (! is_numeric($level)) {
            throw new InvalidArgumentException(sprintf(
                'The %s "%s" level must be an integer; received %s',
                $type,
                $spec['name'],
                get_debug_type($level)
            ));
        }

        $options = $spec['options'] ?? null;
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        return [$spec['name'], (int) $level, is_array($options) ? $options : null];
    }

    /**
     * Register logging system as an error handler to log PHP errors
     *
     * Returns the previous error handler (null when there was none), or false when already registered.
     *
     * @link https://www.php.net/manual/function.set-error-handler.php
     */
    public static function registerErrorHandler(Logger $logger, bool $continueNativeHandler = false): bool|null|callable
    {
        // Only register once per instance
        if (static::$registeredErrorHandler) {
            return false;
        }

        $previous = set_error_handler(static::createErrorHandler($logger, $continueNativeHandler));

        static::$registeredErrorHandler = true;
        return $previous;
    }

    /**
     * The error handler registered by registerErrorHandler(). It logs errors included in error_reporting().
     *
     * @return Closure(int, string, string, int): bool
     */
    public static function createErrorHandler(Logger $logger, bool $continueNativeHandler = false): Closure
    {
        $errorLevelMap = static::$errorLevelMap;

        return static function (
            int $errno,
            string $message,
            string $file,
            int $line
        ) use (
            $logger,
            $errorLevelMap,
            $continueNativeHandler
        ): bool {
            if (error_reporting() & $errno) {
                $logger->log($errorLevelMap[$errno] ?? self::INFO, $message, [
                    'errno' => $errno,
                    'file'  => $file,
                    'line'  => $line,
                ]);
            }

            return ! $continueNativeHandler;
        };
    }

    public static function unregisterErrorHandler(): void
    {
        restore_error_handler();
        static::$registeredErrorHandler = false;
    }

    /**
     * Register a shutdown handler to log fatal errors
     *
     * @link http://www.php.net/manual/function.register-shutdown-function.php
     */
    public static function registerFatalErrorShutdownFunction(Logger $logger): bool
    {
        // Only register once per instance
        if (static::$registeredFatalErrorShutdownFunction) {
            return false;
        }

        $errorLevelMap = static::$errorLevelMap;

        register_shutdown_function(static function () use ($logger, $errorLevelMap) {
            $error = error_get_last();

            if (
                null === $error
                || ! in_array(
                    $error['type'],
                    [
                        E_ERROR,
                        E_PARSE,
                        E_CORE_ERROR,
                        E_CORE_WARNING,
                        E_COMPILE_ERROR,
                        E_COMPILE_WARNING,
                    ],
                    true
                )
            ) {
                return;
            }

            $logger->log(
                $errorLevelMap[$error['type']] ?? self::ERR,
                $error['message'],
                [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]
            );
        });

        static::$registeredFatalErrorShutdownFunction = true;

        return true;
    }

    /**
     * Register a logging system as an exception handler to log PHP exceptions
     *
     * @link https://www.php.net/manual/en/function.set-exception-handler.php
     */
    public static function registerExceptionHandler(Logger $logger): bool
    {
        // Only register once per instance
        if (static::$registeredExceptionHandler) {
            return false;
        }

        set_exception_handler(static::createExceptionHandler($logger));

        static::$registeredExceptionHandler = true;
        return true;
    }

    /**
     * The exception handler registered by registerExceptionHandler(). It logs the exception and every previous
     * exception, the innermost first.
     *
     * @return Closure(Throwable): void
     */
    public static function createExceptionHandler(Logger $logger): Closure
    {
        $errorLevelMap = static::$errorLevelMap;

        return static function (Throwable $exception) use ($logger, $errorLevelMap): void {
            $logMessages = [];

            do {
                $level = self::ERR;
                if ($exception instanceof ErrorException && isset($errorLevelMap[$exception->getSeverity()])) {
                    $level = $errorLevelMap[$exception->getSeverity()];
                }

                $logMessages[] = [
                    'level'   => $level,
                    'message' => $exception->getMessage(),
                    'extra'   => [
                        'file'  => $exception->getFile(),
                        'line'  => $exception->getLine(),
                        'trace' => $exception->getTrace(),
                    ],
                ];
                $exception     = $exception->getPrevious();
            } while ($exception !== null);

            foreach (array_reverse($logMessages) as $logMessage) {
                $logger->log($logMessage['level'], $logMessage['message'], $logMessage['extra']);
            }
        };
    }

    public static function unregisterExceptionHandler(): void
    {
        restore_exception_handler();
        static::$registeredExceptionHandler = false;
    }
}
