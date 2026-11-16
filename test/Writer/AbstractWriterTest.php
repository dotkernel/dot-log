<?php

declare(strict_types=1);

namespace DotTest\Log\Writer;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Filter\Level;
use Dot\Log\Formatter\Simple;
use Dot\Log\Logger;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Dot\Log\Writer\AbstractWriter;
use DotTest\Log\Fixture\MemoryWriter;
use DotTest\Log\Fixture\RecordingFilter;
use Laminas\Stdlib\ErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

use function file_get_contents;

class AbstractWriterTest extends TestCase
{
    /**
     * Regression test: options of a formatter given by class name were dropped.
     */
    public function testFormatterOptionsAreUsedWithAClassName(): void
    {
        $options = ['formatter' => ['name' => Simple::class, 'options' => ['format' => '%message%']]];
        $writer  = new class ($options) extends MemoryWriter {
            /**
             * @param array<string, mixed> $event
             */
            public function line(array $event): string
            {
                return (string) $this->getFormatter()?->format($event);
            }
        };

        $this->assertSame('hello', $writer->line(['message' => 'hello']));
    }

    public function testFiltersFromOptions(): void
    {
        $recording = new RecordingFilter();
        $writer    = new MemoryWriter(new ArrayObject([
            'filters' => [
                Logger::ERR,
                $recording,
                ['name' => 'regex', 'options' => ['regex' => '/x/']],
                ['name' => 'suppress', 'options' => ['suppress' => true]],
            ],
        ]));

        $writer->write(['level' => Logger::ERR, 'message' => 'x']);

        $this->assertSame([], $writer->events, 'the suppress filter blocks the event');
        $this->assertCount(1, $recording->events);
    }

    public function testSingleFilterOption(): void
    {
        $writer = new MemoryWriter(['filters' => Logger::ERR]);

        $writer->write(['level' => Logger::INFO]);
        $writer->write(['level' => Logger::ERR]);

        $this->assertCount(1, $writer->events);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidOptions(): iterable
    {
        yield 'filter without name' => [
            ['filters' => [['options' => []]]],
            'Options must contain a name for the filter',
        ];
        yield 'formatter without name' => [
            ['formatter' => ['options' => []]],
            'Options must contain a name for the formatter',
        ];
        yield 'filter manager' => [
            ['filter_manager' => new stdClass()],
            'Option "filter_manager" must be a plugin manager instance or class name; received stdClass',
        ];
        yield 'formatter manager' => [
            ['formatter_manager' => 1],
            'Option "formatter_manager" must be a plugin manager instance or class name; received int',
        ];
        yield 'filter manager class' => [
            ['filter_manager' => stdClass::class],
            'Filter plugin manager must extend ' . FilterPluginManager::class . '; received stdClass',
        ];
        yield 'formatter manager class' => [
            ['formatter_manager' => stdClass::class],
            'Formatter plugin manager must extend ' . FormatterPluginManager::class . '; received stdClass',
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideInvalidOptions')]
    public function testInvalidOptions(array $options, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new MemoryWriter($options);
    }

    public function testManagersByClassName(): void
    {
        $writer = new MemoryWriter([
            'filter_manager'    => FilterPluginManager::class,
            'formatter_manager' => FormatterPluginManager::class,
        ]);

        $this->assertInstanceOf(Level::class, $writer->filterPlugin('level', ['level' => 1]));
        $this->assertInstanceOf(Simple::class, $writer->formatterPlugin('simple'));
    }

    public function testWarningsDuringWriteBecomeExceptions(): void
    {
        $writer = new class extends AbstractWriter {
            /**
             * @param array<string, mixed> $event
             */
            protected function doWrite(array $event): void
            {
                file_get_contents('/dot-log-test/does-not-exist');
            }
        };

        try {
            $writer->write([]);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to write', $exception->getMessage());
            $this->assertStringContainsString(
                'Failed to open stream',
                (string) $exception->getPrevious()?->getMessage()
            );
        }

        $this->assertFalse(ErrorHandler::started());
    }

    /**
     * Regression test: an Error thrown by doWrite() left the error handler started.
     */
    public function testErrorHandlerIsStoppedWhenWriteThrowsAnError(): void
    {
        $writer = new class extends AbstractWriter {
            /**
             * @param array<string, mixed> $event
             */
            protected function doWrite(array $event): void
            {
                throw new TypeError('broken writer');
            }
        };

        try {
            $writer->write([]);
            $this->fail('Expected TypeError');
        } catch (TypeError $error) {
            $this->assertSame('broken writer', $error->getMessage());
        }

        $this->assertFalse(ErrorHandler::started());
    }

    public function testErrorConversionCanBeDisabled(): void
    {
        $writer = new MemoryWriter();
        $writer->setConvertWriteErrorsToExceptions(false);

        $writer->write(['message' => 'x']);

        $this->assertCount(1, $writer->events);
    }
}
