<?php
/**
 * @copyright: DotKernel
 * @library: dot-log.
 * @author: n3vrax
 * Date: 1/13/2017
 * Time: 9:32 PM
 */

namespace Dot\Log\Factory;

use Interop\Container\ContainerInterface;
use Zend\Log\FormatterPluginManager;

/**
 * Class FormatterPluginManagerFactory
 * @package Dot\Log\Factory
 */
class FormatterPluginManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $config = isset($config['dot_log']) && isset($config['dot_log']['formatter_manager'])
            ? $config['dot_log']['formatter_manager'] : [];

        return new FormatterPluginManager($container, $config);
    }
}
