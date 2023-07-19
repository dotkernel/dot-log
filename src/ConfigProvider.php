<?php

declare(strict_types=1);

namespace Dot\Log;

use Dot\Log\Factory\FilterPluginManagerFactory;
use Dot\Log\Factory\FormatterPluginManagerFactory;
use Dot\Log\Factory\LoggerAbstractServiceFactory;
use Dot\Log\Factory\ProcessorPluginManagerFactory;
use Dot\Log\Factory\WriterPluginManagerFactory;
use Laminas\Log\FilterPluginManager;
use Laminas\Log\FormatterPluginManager;
use Laminas\Log\Logger;
use Laminas\Log\LoggerServiceFactory;
use Laminas\Log\ProcessorPluginManager;
use Laminas\Log\WriterPluginManager;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'dot_log'      => [
                'formatter_manager' => [],
                'filter_manager'    => [],
                'processor_manager' => [],
                'writer_manager'    => [],
                'loggers'           => [],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
            ],
            'aliases'            => [
                FilterPluginManager::class    => 'LogFilterManager',
                FormatterPluginManager::class => 'LogFormatterManager',
                ProcessorPluginManager::class => 'LogProcessorManager',
                WriterPluginManager::class    => 'LogWriterManager',
            ],
            'factories'          => [
                Logger::class         => LoggerServiceFactory::class,
                'LogFilterManager'    => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager'    => WriterPluginManagerFactory::class,
            ],
        ];
    }
}
