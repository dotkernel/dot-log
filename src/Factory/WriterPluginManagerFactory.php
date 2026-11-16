<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Log\Exception\RuntimeException;
use Dot\Log\Manager\WriterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

/**
 * Optional: creates a WriterPluginManager from config['dot_log']['writer_manager'],
 * for applications that want the manager as a container service.
 */
class WriterPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): WriterPluginManager
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! is_array($config)) {
            throw new RuntimeException('Unable to find config');
        }

        $managerConfig = $config['dot_log']['writer_manager'] ?? [];
        if (! is_array($managerConfig)) {
            throw new RuntimeException('The dot_log.writer_manager config must be an array');
        }

        return new WriterPluginManager($container, $managerConfig);
    }
}
