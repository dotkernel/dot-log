<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Log\Exception\RuntimeException;
use Dot\Log\Logger;
use Dot\Log\LoggerServiceFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function date;
use function is_array;
use function is_string;
use function pathinfo;
use function preg_replace_callback;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

use const PATHINFO_BASENAME;

/**
 * Creates the logger configured under config['dot_log']['loggers'][<name>] for the service "dot-log.<name>".
 *
 * Register one entry per logger, with any PSR-11 container that passes the requested name to the factory:
 *
 *     'factories' => [
 *         'dot-log.default_logger' => \Dot\Log\Factory\LoggerFactory::class,
 *     ],
 */
class LoggerFactory extends LoggerServiceFactory
{
    public const PREFIX = 'dot-log.';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $requestedName = ''): Logger
    {
        if (! str_starts_with($requestedName, self::PREFIX) || strlen($requestedName) === strlen(self::PREFIX)) {
            throw new RuntimeException(sprintf(
                'LoggerFactory creates services named "%s<logger name>"; "%s" was requested',
                self::PREFIX,
                $requestedName
            ));
        }
        $name = substr($requestedName, strlen(self::PREFIX));

        $loggers = $this->getApplicationConfig($container)['dot_log']['loggers'] ?? null;
        if (! is_array($loggers)) {
            throw new RuntimeException('Unable to find the "dot_log.loggers" config');
        }

        $config = $loggers[$name] ?? null;
        if (! is_array($config)) {
            throw new RuntimeException(sprintf('Unable to find the "dot_log.loggers.%s" config', $name));
        }

        $this->resolveStreamDates($config);
        $this->processConfig($config, $container);

        return new Logger($config);
    }

    /**
     * Replaces date placeholders such as {Y}, {m} or {d} in writer streams with the current date, and keeps the
     * original file name as "stream_format" for the log clean-up of the Stream writer.
     *
     * @param array<array-key, mixed> $config
     */
    protected function resolveStreamDates(array &$config): void
    {
        if (! isset($config['writers']) || ! is_array($config['writers'])) {
            return;
        }

        foreach ($config['writers'] as $index => $writerConfig) {
            $stream = is_array($writerConfig) ? $writerConfig['options']['stream'] ?? null : null;
            if (! is_string($stream) || $stream === '') {
                continue;
            }

            $config['writers'][$index]['options']['stream_format'] = pathinfo($stream, PATHINFO_BASENAME);
            $config['writers'][$index]['options']['stream']        = self::parseVariables($stream);
        }
    }

    public static function parseVariables(string $stream): string
    {
        return (string) preg_replace_callback(
            '/\{([a-z])}/i',
            static fn (array $match): string => date($match[1]),
            $stream
        );
    }
}
