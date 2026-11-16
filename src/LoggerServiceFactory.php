<?php

declare(strict_types=1);

namespace Dot\Log;

use ArrayAccess;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Traversable;

use function get_debug_type;
use function is_array;
use function is_string;
use function iterator_to_array;
use function sprintf;

/**
 * Creates the logger registered as Dot\Log\Logger, configured under the "log" config key.
 *
 * Works with any PSR-11 container that calls factories as $factory($container, $requestedName).
 */
class LoggerServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $requestedName = Logger::class): Logger
    {
        $config    = $this->getApplicationConfig($container);
        $logConfig = $config['log'] ?? [];
        if ($logConfig instanceof Traversable) {
            $logConfig = iterator_to_array($logConfig);
        }
        if (! is_array($logConfig)) {
            throw new InvalidArgumentException(sprintf(
                'config "log" must be an array; %s provided',
                get_debug_type($logConfig)
            ));
        }

        $this->processConfig($logConfig, $container);

        return new Logger($logConfig);
    }

    /**
     * Injects the plugin managers and validates the writers.
     *
     * Plugin managers named in the logger config are fetched from the container. Otherwise, they are built from the
     * "dot_log" config keys "writer_manager", "filter_manager", "formatter_manager" and "processor_manager".
     *
     * @param array<array-key, mixed> $config
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function processConfig(array &$config, ContainerInterface $services): void
    {
        $writerManager = $this->fromContainer($config['writer_plugin_manager'] ?? null, $services);
        if (! $writerManager instanceof WriterPluginManager) {
            $writerManager = $this->createWriterPluginManager($services);
        }
        $config['writer_plugin_manager'] = $writerManager;

        $processorManager = $this->fromContainer($config['processor_plugin_manager'] ?? null, $services);
        if (! $processorManager instanceof ProcessorPluginManager) {
            $processorManager = new ProcessorPluginManager(
                $services,
                $this->getManagerConfig($services, 'processor_manager')
            );
        }
        $config['processor_plugin_manager'] = $processorManager;

        if (! isset($config['writers'])) {
            return;
        }

        if ($config['writers'] instanceof Traversable) {
            $config['writers'] = iterator_to_array($config['writers']);
        }

        if (! is_array($config['writers'])) {
            unset($config['writers']);
            return;
        }

        foreach ($config['writers'] as $writerConfig) {
            if (! is_array($writerConfig) && ! $writerConfig instanceof ArrayAccess) {
                throw new InvalidArgumentException(
                    'config log.writers[] must contain array or ArrayAccess, '
                    . get_debug_type($writerConfig) . ' provided'
                );
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createWriterPluginManager(ContainerInterface $services): WriterPluginManager
    {
        return new WriterPluginManager(
            $services,
            $this->getManagerConfig($services, 'writer_manager'),
            new FilterPluginManager($services, $this->getManagerConfig($services, 'filter_manager')),
            new FormatterPluginManager($services, $this->getManagerConfig($services, 'formatter_manager')),
        );
    }

    /**
     * @return array<array-key, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getApplicationConfig(ContainerInterface $container): array
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');
        if ($config instanceof Traversable) {
            $config = iterator_to_array($config);
        }

        return is_array($config) ? $config : [];
    }

    /**
     * @return array<array-key, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getManagerConfig(ContainerInterface $container, string $key): array
    {
        $dotLog = $this->getApplicationConfig($container)['dot_log'] ?? [];
        if (! is_array($dotLog) || ! isset($dotLog[$key])) {
            return [];
        }

        if (! is_array($dotLog[$key])) {
            throw new InvalidArgumentException(sprintf('config "dot_log.%s" must be an array', $key));
        }

        return $dotLog[$key];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function fromContainer(mixed $service, ContainerInterface $services): mixed
    {
        if (is_string($service) && $services->has($service)) {
            return $services->get($service);
        }

        return $service;
    }
}
