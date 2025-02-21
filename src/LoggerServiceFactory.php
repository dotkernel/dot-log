<?php

declare(strict_types=1);

namespace Dot\Log;

use ArrayAccess;
use Dot\Log\Exception\InvalidArgumentException;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function gettype;
use function is_array;
use function is_iterable;
use function is_object;
use function is_string;
use function iterator_to_array;

class LoggerServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        // Configure the logger
        $config    = $container->get('config');
        $logConfig = $config['log'] ?? [];

        $this->processConfig($logConfig, $container);

        return new Logger($logConfig);
    }

    /**
     * Process and return the configuration from the container.
     */
    protected function processConfig(array &$config, ContainerInterface $services): void
    {
        if (
            isset($config['writer_plugin_manager'])
            && is_string($config['writer_plugin_manager'])
            && $services->has($config['writer_plugin_manager'])
        ) {
            $config['writer_plugin_manager'] = $services->get($config['writer_plugin_manager']);
        }

        if (
            (! isset($config['writer_plugin_manager'])
                || ! $config['writer_plugin_manager'] instanceof AbstractPluginManager)
            && $services->has('LogWriterManager')
        ) {
            $config['writer_plugin_manager'] = $services->get('LogWriterManager');
        }

        if (
            isset($config['processor_plugin_manager'])
            && is_string($config['processor_plugin_manager'])
            && $services->has($config['processor_plugin_manager'])
        ) {
            $config['processor_plugin_manager'] = $services->get($config['processor_plugin_manager']);
        }

        if (
            (! isset($config['processor_plugin_manager'])
                || ! $config['processor_plugin_manager'] instanceof AbstractPluginManager)
            && $services->has('LogProcessorManager')
        ) {
            $config['processor_plugin_manager'] = $services->get('LogProcessorManager');
        }

        if (! isset($config['writers']) || ! is_iterable($config['writers'])) {
            return;
        }

        if (! is_array($config['writers'])) {
            $config['writers'] = iterator_to_array($config['writers']);
        }

        foreach ($config['writers'] as $writerConfig) {
            if (! is_array($writerConfig) && ! $writerConfig instanceof ArrayAccess) {
                $type = is_object($writerConfig) ? $writerConfig::class : gettype($writerConfig);
                throw new InvalidArgumentException(
                    'config log.writers[] must contain array or ArrayAccess, ' . $type . ' provided'
                );
            }
        }
    }
}
