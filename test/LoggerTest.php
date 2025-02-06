<?php

declare(strict_types=1);

namespace DotTest\Log;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Logger;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\Backtrace;
use Dot\Log\Processor\RequestId;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\Stream;
use ErrorException;
use Exception;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\Stdlib\SplPriorityQueue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function count;
use function set_exception_handler;

use const E_USER_NOTICE;

class LoggerTest extends TestCase
{
    private Logger $subject;

    protected function setUp(): void
    {
        $this->subject = new Logger();
    }

    public function testUsesWriterPluginManagerByDefault(): void
    {
        $this->assertInstanceOf(WriterPluginManager::class, $this->subject->getWriterPluginManager());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testPassingShortNameToPluginReturnsWriterByThatName(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'Unable to resolve service "mock" to a factory; are you certain you provided it during configuration?'
        );
        $this->subject->writerPlugin('mock');
    }

    public function testEmptyWriter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No log writer specified');
        $this->subject->log(Logger::INFO, 'test');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testSetWriters(): void
    {
        $writer  = $this->subject->writerPlugin('null');
        $writers = new SplPriorityQueue();
        $writers->insert($writer, 1);
        $this->subject->setWriters($writers);

        $writers = $this->subject->getWriters();
        $this->assertInstanceOf(SplPriorityQueue::class, $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddWriterWithLevel(): void
    {
        $writer = $this->subject->writerPlugin('null');
        $this->subject->addWriter($writer, 3);
        $writers = $this->subject->getWriters();

        $this->assertInstanceOf(SplPriorityQueue::class, $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddWithSameLevel(): void
    {
        $writer1 = $this->subject->writerPlugin('null');
        $this->subject->addWriter($writer1, 1);
        $writer2 = $this->subject->writerPlugin('null');
        $this->subject->addWriter($writer2, 1);
        $writers = $this->subject->getWriters();

        $this->assertInstanceOf(SplPriorityQueue::class, $writers);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
        $writer = $writers->extract();
        $this->assertInstanceOf(Noop::class, $writer);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testLogging(): void
    {
        $writer = new Mock();
        $this->subject->addWriter($writer);
        $this->subject->log(Logger::INFO, 'tottakai');

        $this->assertEquals(1, count($writer->events));
        $this->assertStringContainsString('tottakai', $writer->events[0]['message']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testLoggingStringable(): void
    {
        $test = new class {
            public function __toString(): string
            {
                return 'test';
            }
        };

        $writer = new Mock();
        $this->subject->addWriter($writer);
        $this->subject->log(Logger::INFO, $test);

        $this->assertEquals(1, count($writer->events));
        $this->assertStringContainsString('test', $writer->events[0]['message']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddFilter(): void
    {
        $writer = new Mock();
        $filter = new MockFilter();
        $writer->addFilter($filter);
        $this->subject->addWriter($writer);
        $this->subject->log(Logger::INFO, 'test');

        $this->assertEquals(1, count($filter->events));
        $this->assertStringContainsString('test', $filter->events[0]['message']);
    }

    public static function provideTestFilters(): array
    {
        return [
            ['level', ['level' => Logger::INFO]],
            ['regex', ['regex' => '/[0-9]+/']],
        ];
    }

    /**
     * @dataProvider provideTestFilters
     * @throws ContainerExceptionInterface
     */
    public function testAddFilterByNameWithParams(string $filter, array $options): void
    {
        $writer = new Mock();
        $writer->addFilter($filter, $options);
        $this->subject->addWriter($writer);

        $this->subject->log(Logger::INFO, '123');
        $this->assertEquals(1, count($writer->events));
        $this->assertStringContainsString('123', $writer->events[0]['message']);
    }

    public static function provideAttributes(): array
    {
        return [
            [[]],
            [['user' => 'foo', 'ip' => '127.0.0.1']],
            [new ArrayObject(['id' => 42])],
        ];
    }

    /**
     * @dataProvider provideAttributes
     * @throws ContainerExceptionInterface
     */
    public function testLoggingCustomAttributesForUserContext(array|ArrayObject $context): void
    {
        $writer = new Mock();
        $this->subject->addWriter($writer);
        $this->subject->log(Logger::ERR, 'tottakai', $context);

        $this->assertEquals(1, count($writer->events));
        $this->assertIsArray($writer->events[0]['context']);
        $this->assertEquals(count($writer->events[0]['context']), count($context));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testRegisterErrorHandler(): void
    {
        $writer = new Mock();
        $this->subject->addWriter($writer);

        $previous = Logger::registerErrorHandler($this->subject);
        $this->assertNotNull($previous);
        $this->assertNotFalse($previous);

        // check for single error handler instance
        $this->assertFalse(Logger::registerErrorHandler($this->subject));

        // generate a warning
        echo $test; // $test is not defined

        Logger::unregisterErrorHandler();

        $this->assertEquals('Undefined variable $test', $writer->events[0]['message']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testOptionsWithMock(): void
    {
        $options = [
            'writers' => [
                'first_writer' => [
                    'name'  => 'null',
                    'level' => 1,
                ],
            ],
        ];
        $logger  = new Logger($options);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Noop::class, $writers[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testOptionsWithWriterOptions(): void
    {
        $options = [
            'writers' => [
                [
                    'name'    => 'stream',
                    'options' => [
                        'stream'        => 'php://output',
                        'log_separator' => 'foo',
                    ],
                    'level'   => 1,
                ],
            ],
        ];
        $logger  = new Logger($options);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Stream::class, $writers[0]);
        $this->assertEquals('foo', $writers[0]->getLogSeparator());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testOptionsWithMockAndProcessor(): void
    {
        $options    = [
            'writers'    => [
                'first_writer' => [
                    'name'  => 'null',
                    'level' => 1,
                ],
            ],
            'processors' => [
                'first_processor' => [
                    'name'  => 'requestid',
                    'level' => 1,
                ],
            ],
        ];
        $logger     = new Logger($options);
        $processors = $logger->getProcessors()->toArray();
        $this->assertCount(1, $processors);
        $this->assertInstanceOf(RequestId::class, $processors[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddProcessor(): void
    {
        $processor = new Backtrace();
        $this->subject->addProcessor($processor);

        $processors = $this->subject->getProcessors()->toArray();
        $this->assertEquals($processor, $processors[0]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddProcessorByName(): void
    {
        $this->subject->addProcessor('backtrace');

        $processors = $this->subject->getProcessors()->toArray();
        $this->assertInstanceOf(Backtrace::class, $processors[0]);

        $writer = new Mock();
        $this->subject->addWriter($writer);
        $this->subject->log(Logger::ERR, 'foo');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testExceptionHandler(): void
    {
        $writer = new Mock();
        $this->subject->addWriter($writer);

        $this->assertTrue(Logger::registerExceptionHandler($this->subject));

        // check for single error handler instance
        $this->assertFalse(Logger::registerExceptionHandler($this->subject));

        // get the internal exception handler
        $exceptionHandler = set_exception_handler(function ($e) {
        });
        set_exception_handler($exceptionHandler);

        // reset the exception handler
        Logger::unregisterExceptionHandler();

        // call the exception handler
        $exceptionHandler(new Exception('error', 200, new Exception('previos', 100)));
        $exceptionHandler(new ErrorException('user notice', 1_000, E_USER_NOTICE, __FILE__, __LINE__));

        // check logged messages
        $expectedEvents = [
            ['level' => Logger::ERR,    'message' => 'previos',     'file' => __FILE__],
            ['level' => Logger::ERR,    'message' => 'error',       'file' => __FILE__],
            ['level' => Logger::NOTICE, 'message' => 'user notice', 'file' => __FILE__],
        ];
        for ($i = 0; $i < count($expectedEvents); $i++) {
            $expectedEvent = $expectedEvents[$i];
            $event         = $writer->events[$i];

            $this->assertEquals($expectedEvent['level'], $event['level'], 'Unexpected level');
            $this->assertEquals($expectedEvent['message'], $event['message'], 'Unexpected message');
            $this->assertEquals($expectedEvent['file'], $event['context']['file'], 'Unexpected file');
        }
    }

    /**
     * @group Laminas-7238
     * @throws ContainerExceptionInterface
     */
    public function testCatchExceptionNotValidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$level must be an integer >= 0 and < 8; received -1');
        $writer = new Mock();
        $this->subject->addWriter($writer);
        $this->subject->log(-1, 'Foo');
    }
}
