<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Exception\RuntimeException;
use Dot\Log\Factory\FilterPluginManagerFactory;
use Dot\Log\Factory\FormatterPluginManagerFactory;
use Dot\Log\Factory\ProcessorPluginManagerFactory;
use Dot\Log\Factory\WriterPluginManagerFactory;
use Dot\Log\Filter\SuppressFilter;
use Dot\Log\Formatter\Json;
use Dot\Log\Manager\AbstractPluginManager;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\RequestId;
use Dot\Log\Writer\Noop;
use DotTest\Log\Fixture\InMemoryContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PluginManagerFactoryTest extends TestCase
{
    /**
     * @return iterable<string, array{callable, string, class-string<AbstractPluginManager<object>>, class-string}>
     */
    public static function provideFactories(): iterable
    {
        yield 'filter' => [
            new FilterPluginManagerFactory(),
            'filter_manager',
            FilterPluginManager::class,
            SuppressFilter::class,
        ];
        yield 'formatter' => [
            new FormatterPluginManagerFactory(),
            'formatter_manager',
            FormatterPluginManager::class,
            Json::class,
        ];
        yield 'processor' => [
            new ProcessorPluginManagerFactory(),
            'processor_manager',
            ProcessorPluginManager::class,
            RequestId::class,
        ];
        yield 'writer' => [
            new WriterPluginManagerFactory(),
            'writer_manager',
            WriterPluginManager::class,
            Noop::class,
        ];
    }

    /**
     * @param class-string<AbstractPluginManager<object>> $managerClass
     */
    #[DataProvider('provideFactories')]
    public function testCreatesAnEmptyManagerWithoutConfig(
        callable $factory,
        string $key,
        string $managerClass,
        string $pluginClass
    ): void {
        $this->assertFalse((new $managerClass())->has('custom'), $pluginClass . ' is not registered by default');
        $this->assertInstanceOf($managerClass, $factory(new InMemoryContainer()));
        $this->assertInstanceOf($managerClass, $factory(new InMemoryContainer(['config' => []])));
    }

    /**
     * Regression test: the factories passed the whole dot_log config, so custom plugins were ignored.
     *
     * @param class-string<AbstractPluginManager<object>> $managerClass
     * @param class-string $pluginClass
     */
    #[DataProvider('provideFactories')]
    public function testAppliesTheManagerConfig(
        callable $factory,
        string $key,
        string $managerClass,
        string $pluginClass
    ): void {
        $container = new InMemoryContainer([
            'config' => ['dot_log' => [$key => ['aliases' => ['custom' => $pluginClass]]]],
        ]);

        $manager = $factory($container);

        $this->assertInstanceOf(AbstractPluginManager::class, $manager);
        $this->assertInstanceOf($pluginClass, $manager->build('custom'));
        $this->assertSame($container, $manager->getCreationContext());
    }

    #[DataProvider('provideFactories')]
    public function testRejectsANonArrayManagerConfig(
        callable $factory,
        string $key,
        string $managerClass,
        string $pluginClass
    ): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The dot_log.' . $key . ' config must be an array');

        $factory(new InMemoryContainer(['config' => ['dot_log' => [$key => 'invalid']]]));
    }

    #[DataProvider('provideFactories')]
    public function testRejectsANonArrayConfig(
        callable $factory,
        string $key,
        string $managerClass,
        string $pluginClass
    ): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find config');

        $factory(new InMemoryContainer(['config' => 'invalid']));
    }
}
