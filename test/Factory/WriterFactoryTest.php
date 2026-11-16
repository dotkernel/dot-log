<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Factory\WriterFactory;
use Dot\Log\Formatter\Json;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Dot\Log\Writer\Stream;
use DotTest\Log\Fixture\InMemoryContainer;
use DotTest\Log\Fixture\MemoryWriter;
use PHPUnit\Framework\TestCase;
use stdClass;

class WriterFactoryTest extends TestCase
{
    public function testCreatesTheWriterWithOptions(): void
    {
        $writer = (new WriterFactory())(new InMemoryContainer(), Stream::class, [
            'stream'        => 'php://memory',
            'log_separator' => '|',
        ]);

        $this->assertInstanceOf(Stream::class, $writer);
        $this->assertSame('|', $writer->getLogSeparator());
    }

    public function testInjectsDefaultManagerServicesWhenTheContainerHasThem(): void
    {
        $filters    = new FilterPluginManager();
        $formatters = new FormatterPluginManager(null, ['aliases' => ['custom' => Json::class]]);
        $container  = new InMemoryContainer([
            'LogFilterManager'    => $filters,
            'LogFormatterManager' => $formatters,
        ]);

        $writer = (new WriterFactory())($container, MemoryWriter::class);

        $this->assertInstanceOf(MemoryWriter::class, $writer);
        $this->assertSame($filters, $writer->getFilterPluginManager());
        $this->assertSame($formatters, $writer->getFormatterPluginManager());
    }

    public function testResolvesManagersNamedAsServices(): void
    {
        $formatters = new FormatterPluginManager();
        $container  = new InMemoryContainer(['my.formatters' => $formatters]);

        $writer = (new WriterFactory())($container, MemoryWriter::class, ['formatter_manager' => 'my.formatters']);

        $this->assertInstanceOf(MemoryWriter::class, $writer);
        $this->assertSame($formatters, $writer->getFormatterPluginManager());
    }

    public function testRejectsClassesThatAreNotWriters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"stdClass" is not a class implementing Dot\Log\Writer\WriterInterface');

        (new WriterFactory())(new InMemoryContainer(), stdClass::class);
    }
}
