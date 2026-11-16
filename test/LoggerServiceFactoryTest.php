<?php

declare(strict_types=1);

namespace DotTest\Log;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Logger;
use Dot\Log\LoggerServiceFactory;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\PsrPlaceholder;
use Dot\Log\Writer\Noop;
use DotTest\Log\Fixture\InMemoryContainer;
use DotTest\Log\Fixture\MemoryWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class LoggerServiceFactoryTest extends TestCase
{
    public function testCreatesALoggerFromTheLogConfig(): void
    {
        $container = new InMemoryContainer(['config' => ['log' => ['writers' => [['name' => 'noop']]]]]);

        $logger = (new LoggerServiceFactory())($container, Logger::class);

        $this->assertCount(1, $logger->getWriters());
    }

    public function testCreatesALoggerWithoutConfig(): void
    {
        $this->assertCount(0, (new LoggerServiceFactory())(new InMemoryContainer())->getWriters());
    }

    public function testWillInjectWriterPluginManagerNamedAsService(): void
    {
        $writers   = new WriterPluginManager(null, ['aliases' => ['memory' => MemoryWriter::class]]);
        $container = new InMemoryContainer([
            'MyWriters' => $writers,
            'config'    => [
                'log' => [
                    'writer_plugin_manager' => 'MyWriters',
                    'writers'               => [['name' => 'memory', 'level' => 1]],
                ],
            ],
        ]);

        $logger = (new LoggerServiceFactory())($container, Logger::class);

        $this->assertSame($writers, $logger->getWriterPluginManager());
        $this->assertInstanceOf(MemoryWriter::class, $logger->getWriters()->current());
    }

    public function testWillInjectProcessorPluginManagerNamedAsService(): void
    {
        $processors = new ProcessorPluginManager();
        $container  = new InMemoryContainer([
            'MyProcessors' => $processors,
            'config'       => [
                'log' => [
                    'processor_plugin_manager' => 'MyProcessors',
                    'writers'                  => [['name' => Noop::class, 'level' => 1]],
                    'processors'               => [['name' => 'psrplaceholder', 'level' => 1]],
                ],
            ],
        ]);

        $logger = (new LoggerServiceFactory())($container, Logger::class);

        $this->assertSame($processors, $logger->getProcessorPluginManager());
        $this->assertInstanceOf(PsrPlaceholder::class, $logger->getProcessors()->current());
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function dataWritersValues(): iterable
    {
        yield 'null' => [null, 0];
        yield 'string' => ['writers config', 0];
        yield 'number' => [1e3, 0];
        yield 'object' => [new stdClass(), 0];
        yield 'empty iterable' => [new ArrayObject(), 0];
        yield 'iterable' => [new ArrayObject([['name' => Noop::class, 'level' => 1]]), 1];
    }

    #[DataProvider('dataWritersValues')]
    public function testWritersValue(mixed $writers, int $count): void
    {
        $container = new InMemoryContainer(['config' => ['log' => ['writers' => $writers]]]);

        $this->assertCount($count, (new LoggerServiceFactory())($container, Logger::class)->getWriters());
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function dataInvalidWriterConfig(): iterable
    {
        yield 'string' => ['invalid config', 'string'];
        yield 'object' => [new stdClass(), 'stdClass'];
    }

    #[DataProvider('dataInvalidWriterConfig')]
    public function testInvalidWriterConfig(mixed $value, string $type): void
    {
        $container = new InMemoryContainer(['config' => ['log' => ['writers' => [$value]]]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('config log.writers[] must contain array or ArrayAccess, ' . $type . ' provided');

        (new LoggerServiceFactory())($container, Logger::class);
    }

    public function testInvalidLogConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('config "log" must be an array; string provided');

        (new LoggerServiceFactory())(new InMemoryContainer(['config' => ['log' => 'x']]), Logger::class);
    }

    public function testInvalidManagerConfig(): void
    {
        $container = new InMemoryContainer(['config' => ['dot_log' => ['writer_manager' => 'x']]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('config "dot_log.writer_manager" must be an array');

        (new LoggerServiceFactory())($container, Logger::class);
    }
}
