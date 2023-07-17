<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Exception;
use Laminas\Log\FormatterPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

class FormatterPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container): FormatterPluginManager
    {
        if (! $container->has('config')) {
            throw new Exception('Unable to find config');
        }

        $config = $container->get('config');

        if (! isset($config['dot_log']) || ! is_array($config['dot_log'])) {
            throw new Exception('Unable to find dot_log config');
        }

        $config = $config['dot_log'];

        if (! isset($config['formatter_manager']) || ! is_array($config['formatter_manager'])) {
            throw new Exception('Unable to find formatter_manager config');
        }

        return new FormatterPluginManager($container, $config);
    }
}
