<?php
/**
 * @copyright: DotKernel
 * @library: dot-log.
 * @author: n3vrax
 * Date: 1/13/2017
 * Time: 9:34 PM
 */

namespace Dot\Log\Factory;

use Interop\Container\ContainerInterface;
use Zend\Log\FilterPluginManager;

/**
 * Class FilterPluginManagerFactory
 * @package Dot\Log\Factory
 */
class FilterPluginManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $config = isset($config['dot_log']) && isset($config['dot_log']['filter_manager'])
            ? $config['dot_log']['filter_manager'] : [];

        return new FilterPluginManager($container, $config);
    }
}
