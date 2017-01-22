<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-log
 * @author: n3vrax
 * Date: 1/11/2017
 * Time: 8:44 PM
 */

namespace Dot\Log;

use Dot\Log\Factory\FilterPluginManagerFactory;
use Dot\Log\Factory\FormatterPluginManagerFactory;
use Dot\Log\Factory\ProcessorPluginManagerFactory;
use Dot\Log\Factory\WriterPluginManagerFactory;
use Zend\Log\Logger;
use Zend\Log\LoggerServiceFactory;

/**
 * Class ConfigProvider
 * @package Dot\Log
 */
class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),

            'dot_log' => [

                'formatter_manager' => [],

                'filter_manager' => [],

                'processor_manager' => [],

                'writer_manager' => [],

                'loggers' => [],

            ],
        ];
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
            ],

            'factories' => [
                Logger::class => LoggerServiceFactory::class,
                'LogFilterManager' => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager' => WriterPluginManagerFactory::class,
            ]
        ];
    }
}
