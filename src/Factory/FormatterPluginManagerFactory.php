<?php
/**
 * @see https://github.com/dotkernel/dot-log/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-log/blob/master/LICENSE.md MIT License
 */

declare(strict_types = 1);

namespace Dot\Log\Factory;

use Psr\Container\ContainerInterface;
use Laminas\Log\FormatterPluginManager;

/**
 * Class FormatterPluginManagerFactory
 * @package Dot\Log\Factory
 */
class FormatterPluginManagerFactory
{
    /**
     * @param ContainerInterface $container
     * @return FormatterPluginManager
     */
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
