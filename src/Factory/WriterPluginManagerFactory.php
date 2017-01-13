<?php
/**
 * @copyright: DotKernel
 * @library: dot-log.
 * @author: n3vrax
 * Date: 1/13/2017
 * Time: 8:06 PM
 */

namespace Dot\Log\Factory;

use Interop\Container\ContainerInterface;
use Zend\Log\WriterPluginManager;

/**
 * Class WriterPluginManagerFactory
 * @package Dot\Log\Factory
 */
class WriterPluginManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $config = isset($config['dot_log']) && isset($config['dot_log']['writer_manager'])
            ? $config['dot_log']['writer_manager'] : [];

        return new WriterPluginManager($container, $config);
    }
}
