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
use Zend\Log\FilterPluginManager;
use Zend\Log\FormatterPluginManager;
use Zend\Log\Logger;
use Zend\Log\LoggerServiceFactory;
use Zend\Log\ProcessorPluginManager;
use Zend\Log\WriterPluginManager;

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
            'aliases' => [
                FilterPluginManager::class => 'LogFilterManager',
                FormatterPluginManager::class => 'LogFormatterManager',
                ProcessorPluginManager::class => 'LogProcessorManager',
                WriterPluginManager::class => 'LogWriterManager',
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
