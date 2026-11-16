<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Log\Exception\RuntimeException;
use Dot\Log\Manager\ProcessorPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

/**
 * Optional: creates a ProcessorPluginManager from config['dot_log']['processor_manager'],
 * for applications that want the manager as a container service.
 */
class ProcessorPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessorPluginManager
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! is_array($config)) {
            throw new RuntimeException('Unable to find config');
        }

        $managerConfig = $config['dot_log']['processor_manager'] ?? [];
        if (! is_array($managerConfig)) {
            throw new RuntimeException('The dot_log.processor_manager config must be an array');
        }

        return new ProcessorPluginManager($container, $managerConfig);
    }
}
