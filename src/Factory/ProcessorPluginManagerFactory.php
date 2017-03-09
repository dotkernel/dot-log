<?php
/**
 * @copyright: DotKernel
 * @library: dot-log.
 * @author: n3vrax
 * Date: 1/13/2017
 * Time: 8:48 PM
 */

declare(strict_types = 1);

namespace Dot\Log\Factory;

use Interop\Container\ContainerInterface;
use Zend\Log\ProcessorPluginManager;

/**
 * Class ProcessorPluginManagerFactory
 * @package Dot\Log\Factory
 */
class ProcessorPluginManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $config = isset($config['dot_log']) && isset($config['dot_log']['processor_manager'])
            ? $config['dot_log']['processor_manager'] : [];

        return new ProcessorPluginManager($container, $config);
    }
}
