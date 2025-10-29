<?php

declare(strict_types=1);

namespace Dot\Log;

use DateTime;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\ProcessorInterface;
use Dot\Log\Writer\WriterInterface;
use ErrorException;
use Exception;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\SplPriorityQueue;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Traversable;

use function array_reverse;
use function array_search;
use function count;
use function error_get_last;
use function error_reporting;
use function gettype;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
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
     * List of level code => level (short) name
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

    protected SplPriorityQueue $writers;

    protected SplPriorityQueue $processors;

    protected ?WriterPluginManager $writerPlugins = null;

    protected ?ProcessorPluginManager $processorPlugins = null;

    /**
     * Constructor
     *
     * Set options for a logger. Accepted options are:
     * - writers: array of writers to add to this logger
     * - exceptionhandler: if true, register this logger as exceptionhandler
     * - errorhandler: if true, register this logger as errorhandler
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(?iterable $options = null)
    {
        $this->writers    = new SplPriorityQueue();
        $this->processors = new SplPriorityQueue();

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray((array) $options);
        }

        if (! $options) {
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
                if (! isset($writer['name'])) {
                    throw new InvalidArgumentException('Options must contain a name for the writer');
                }

                $level         = $writer['level'] ?? null;
                $writerOptions = $writer['options'] ?? null;

                $this->addWriter($writer['name'], $level, $writerOptions);
            }
        }

        if (isset($options['processors']) && is_array($options['processors'])) {
            foreach ($options['processors'] as $processor) {
                if (! isset($processor['name'])) {
                    throw new InvalidArgumentException('Options must contain a name for the processor');
                }

                $level            = $processor['level'] ?? null;
                $processorOptions = $processor['options'] ?? null;

                $this->addProcessor($processor['name'], $level, $processorOptions);
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
     * Shutdown all writers
     */
    public function __destruct()
    {
        foreach ($this->writers as $writer) {
            try {
                $writer->shutdown();
            } catch (Exception) {
            }
        }
    }

    public function getWriterPluginManager(): ?WriterPluginManager
    {
        if (null === $this->writerPlugins) {
            $this->setWriterPluginManager(new WriterPluginManager(new ServiceManager()));
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
     * @throws ContainerExceptionInterface
     */
    public function writerPlugin(string $name, ?array $options = null): ?WriterInterface
    {
        return $this->getWriterPluginManager()?->build($name, $options);
    }

    /**
     * Add a writer to a logger
     *
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

    public function getWriters(): SplPriorityQueue
    {
        return $this->writers;
    }

    public function setWriters(SplPriorityQueue $writers): static
    {
        foreach ($writers->toArray() as $writer) {
            if (! $writer instanceof Writer\WriterInterface) {
                throw new InvalidArgumentException(
                    'Writers must be a SplPriorityQueue of Laminas\Log\Writer'
                );
            }
        }
        $this->writers = $writers;
        return $this;
    }

    public function getProcessorPluginManager(): ?ProcessorPluginManager
    {
        if (null === $this->processorPlugins) {
            $this->setProcessorPluginManager(new ProcessorPluginManager(new ServiceManager()));
        }
        return $this->processorPlugins;
    }

    /**
     * @param class-string|ProcessorPluginManager $plugins
     */
    public function setProcessorPluginManager(string|ProcessorPluginManager $plugins): static
    {
        if (is_string($plugins)) {
            $plugins = new $plugins();
        }
        if (! $plugins instanceof ProcessorPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'processor plugin manager must extend %s\ProcessorPluginManager; received %s',
                __NAMESPACE__,
                $plugins::class
            ));
        }

        $this->processorPlugins = $plugins;
        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function processorPlugin(string $name, ?array $options = null): ?ProcessorInterface
    {
        return $this->getProcessorPluginManager()?->build($name, $options);
    }

    /**
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

    public function getProcessors(): SplPriorityQueue
    {
        return $this->processors;
    }

    public function log(mixed $level, string|Stringable $message, iterable $context = []): void
    {
        if (! is_numeric($level)) {
            if (isset($this->levels[$level])) {
                $level = $this->levels[$level];
            }
        }

        if (($level < 0) || ($level >= count($this->levels))) {
            throw new InvalidArgumentException(sprintf(
                '$level must be an integer >= 0 and < %d; received %s',
                count($this->levels),
                var_export($level, true)
            ));
        }

        if ($context instanceof Traversable) {
            $context = ArrayUtils::iteratorToArray((array) $context);
        }

        if ($this->writers->count() === 0) {
            throw new RuntimeException('No log writer specified');
        }

        $timestamp = new DateTime();

        $message = $this->handlePlaceholders((string) $message, $context);

        $event = [
            'timestamp' => $timestamp,
            'level'     => $level,
            'levelName' => array_search((int) $level, $this->levels, true),
            'message'   => $message,
            'context'   => $context,
        ];

        /** @var ProcessorInterface $processor */
        foreach ($this->processors->toArray() as $processor) {
            $event = $processor->process($event);
        }

        /** @var WriterInterface $writer */
        foreach ($this->writers->toArray() as $writer) {
            $writer->write($event);
        }
    }

    protected function handlePlaceholders(string $message, array $context): string
    {
        if (preg_match_all('/\{([\w.]+)}/', $message, $matches)) {
            $replacements = [];
            foreach ($matches[1] as $match) {
                if (! isset($context[$match])) {
                    continue;
                }

                $placeholderValue = $context[$match];

                if (is_string($placeholderValue) || is_numeric($placeholderValue)) {
                    $replacements["{{$match}}"] = (string) $placeholderValue;
                } else {
                    $replacements["{{$match}}"] = gettype($placeholderValue);
                }
            }

            $message = strtr($message, $replacements);
        }

        return $message;
    }

    /**
     * Register logging system as an error handler to log PHP errors
     *
     * @link http://www.php.net/manual/function.set-error-handler.php
     */
    public static function registerErrorHandler(Logger $logger, bool $continueNativeHandler = false): bool|null|callable
    {
        // Only register once per instance
        if (static::$registeredErrorHandler) {
            return false;
        }

        $errorLevelMap = static::$errorLevelMap;

        $previous = set_error_handler(
            function ($level, $message, $file, $line) use ($logger, $errorLevelMap, $continueNativeHandler) {
                $iniLevel = error_reporting();

                if ($iniLevel & $level) {
                    if (isset($errorLevelMap[$level])) {
                        $level = $errorLevelMap[$level];
                    } else {
                        $level = Logger::INFO;
                    }
                    $logger->log($level, $message, [
                        'errno' => $level,
                        'file'  => $file,
                        'line'  => $line,
                    ]);
                }

                return ! $continueNativeHandler;
            }
        );

        static::$registeredErrorHandler = true;
        return $previous;
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

        register_shutdown_function(function () use ($logger, $errorLevelMap) {
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
                $errorLevelMap[$error['type']],
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
     * @link http://www.php.net/manual/en/function.set-exception-handler.php
     */
    public static function registerExceptionHandler(Logger $logger): bool
    {
        // Only register once per instance
        if (static::$registeredExceptionHandler) {
            return false;
        }

        $errorLevelMap = static::$errorLevelMap;

        set_exception_handler(function ($exception) use ($logger, $errorLevelMap) {
            $logMessages = [];

            do {
                $level = Logger::ERR;
                if ($exception instanceof ErrorException && isset($errorLevelMap[$exception->getSeverity()])) {
                    $level = $errorLevelMap[$exception->getSeverity()];
                }

                $extra = [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                ];

                $logMessages[] = [
                    'level'   => $level,
                    'message' => $exception->getMessage(),
                    'extra'   => $extra,
                ];
                $exception     = $exception->getPrevious();
            } while ($exception);

            foreach (array_reverse($logMessages) as $logMessage) {
                $logger->log($logMessage['level'], $logMessage['message'], $logMessage['extra']);
            }
        });

        static::$registeredExceptionHandler = true;
        return true;
    }

    public static function unregisterExceptionHandler(): void
    {
        restore_exception_handler();
        static::$registeredExceptionHandler = false;
    }
}
