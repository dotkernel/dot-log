<?php

declare(strict_types=1);

namespace DotLog\Test;

use Dot\Log\ConfigProvider;
use Laminas\Log\FilterPluginManager;
use Laminas\Log\FormatterPluginManager;
use Laminas\Log\Logger;
use Laminas\Log\ProcessorPluginManager;
use Laminas\Log\WriterPluginManager;
use PHPUnit\Framework\TestCase;

class ConfigProvideTest extends TestCase
{
    protected array $config;

    protected function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testHasDependencies(): void
    {
        $this->assertArrayHasKey('dependencies', $this->config);
    }

    public function testDependenciesHasFactories(): void
    {
        $this->assertArrayHasKey('factories', $this->config['dependencies']);
        $this->assertArrayHasKey(Logger::class, $this->config['dependencies']['factories']);
        $this->assertArrayHasKey('LogFilterManager', $this->config['dependencies']['factories']);
        $this->assertArrayHasKey('LogFormatterManager', $this->config['dependencies']['factories']);
        $this->assertArrayHasKey('LogProcessorManager', $this->config['dependencies']['factories']);
        $this->assertArrayHasKey('LogWriterManager', $this->config['dependencies']['factories']);
    }

    public function testDependenciesHasAliases()
    {
        $this->assertArrayHasKey('aliases', $this->config['dependencies']);
        $this->assertArrayHasKey(FilterPluginManager::class, $this->config['dependencies']['aliases']);
        $this->assertArrayHasKey(FormatterPluginManager::class, $this->config['dependencies']['aliases']);
        $this->assertArrayHasKey(ProcessorPluginManager::class, $this->config['dependencies']['aliases']);
        $this->assertArrayHasKey(WriterPluginManager::class, $this->config['dependencies']['aliases']);
    }

    public function testDependenciesHasAbstractFactories()
    {
        $this->assertArrayHasKey('abstract_factories', $this->config['dependencies']);
    }

    public function testConfigHasDotLog(): void
    {
        $this->assertArrayHasKey('dot_log', $this->config);
        $this->assertIsArray($this->config['dot_log']);
    }

    public function testDotLogKeysReturnArray(): void
    {
        foreach ($this->config['dot_log'] as $key) {
            $this->assertIsArray($key);
        }
    }
}
