<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Exception;
use Laminas\Log\ProcessorPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

class ProcessorPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container): ProcessorPluginManager
    {
        if (! $container->has('config')) {
            throw new Exception('Unable to find config');
        }

        $config = $container->get('config');

        if (! isset($config['dot_log']) || ! is_array($config['dot_log'])) {
            throw new Exception('Unable to find dot_log config');
        }

        $config = $config['dot_log'];

        if (! isset($config['processor_manager']) || ! is_array($config['processor_manager'])) {
            throw new Exception('Unable to find processor_manager config');
        }

        return new ProcessorPluginManager($container, $config);
    }
}
