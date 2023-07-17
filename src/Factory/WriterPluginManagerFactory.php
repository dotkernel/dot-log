<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Exception;
use Laminas\Log\WriterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

class WriterPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container): WriterPluginManager
    {
        if (! $container->has('config')) {
            throw new Exception('Unable to find config');
        }

        $config = $container->get('config');

        if (! isset($config['dot_log']) || ! is_array($config['dot_log'])) {
            throw new Exception('Unable to find dot_log config');
        }

        $config = $config['dot_log'];

        if (! isset($config['writer_manager']) || ! is_array($config['writer_manager'])) {
            throw new Exception('Unable to find writer_manager config');
        }

        return new WriterPluginManager($container, $config);
    }
}
