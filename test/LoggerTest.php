<?php

declare(strict_types=1);

namespace DotTest\Log;

use ArrayObject;
use DateTimeImmutable;
use DateTimeInterface;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Exception\ServiceNotFoundException;
use Dot\Log\Logger;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\Backtrace;
use Dot\Log\Processor\RequestId;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\Stream;
use DotTest\Log\Fixture\MemoryWriter;
use DotTest\Log\Fixture\RecordingFilter;
use ErrorException;
use Exception;
use Laminas\Stdlib\SplPriorityQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

use function array_column;
use function count;
use function error_reporting;
use function trigger_error;

use const E_ALL;
use const E_USER_DEPRECATED;
use const E_USER_NOTICE;
use const E_USER_WARNING;

class LoggerTest extends TestCase
{
    private Logger $subject;

    private MemoryWriter $writer;

    protected function setUp(): void
    {
        $this->subject = new Logger();
        $this->writer  = new MemoryWriter();
    }

    /**
     * @return array<string, mixed>
     */
    private function lastEvent(): array
    {
        $this->assertNotEmpty($this->writer->events);

        return $this->writer->events[0];
    }

    public function testDefaultPluginManagersAreCreatedOnce(): void
    {
        $this->assertSame($this->subject->getWriterPluginManager(), $this->subject->getWriterPluginManager());
        $this->assertSame($this->subject->getProcessorPluginManager(), $this->subject->getProcessorPluginManager());
    }

    public function testUnknownWriterPlugin(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Unable to resolve plugin "mock" in ' . WriterPluginManager::class);

        $this->subject->writerPlugin('mock');
    }

    public function testEmptyWriter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No log writer specified');

        $this->subject->log(Logger::INFO, 'test');
    }

    public function testSetWriters(): void
    {
        $writers = new SplPriorityQueue();
        $writers->insert($this->subject->writerPlugin('null'), 1);

        $this->subject->setWriters($writers);

        $this->assertInstanceOf(Noop::class, $this->subject->getWriters()->extract());
    }

    public function testSetWritersRejectsOtherObjects(): void
    {
        $writers = new SplPriorityQueue();
        $writers->insert(new stdClass(), 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Writers must be a SplPriorityQueue of Dot\Log\Writer\WriterInterface');

        $this->subject->setWriters($writers);
    }

    public function testWritersRunByPriority(): void
    {
        $low  = new MemoryWriter();
        $high = new MemoryWriter();
        $this->subject->addWriter($low, 1);
        $this->subject->addWriter($high, 3);

        $this->assertSame([$high, $low], $this->subject->getWriters()->toArray());
    }

    public function testLogging(): void
    {
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::INFO, 'tottakai');
        $this->subject->log(4, 'tottakai');
        $this->subject->log('2', 'tottakai');
        $this->subject->log('debug', 'tottakai');

        $this->assertCount(4, $this->writer->events);
        $this->assertSame([6, 4, 2, 7], array_column($this->writer->events, 'level'));
        $this->assertSame(
            ['info', 'warning', 'critical', 'debug'],
            array_column($this->writer->events, 'levelName')
        );
        $this->assertInstanceOf(DateTimeImmutable::class, $this->lastEvent()['timestamp']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidLevels(): iterable
    {
        yield 'negative' => [-1];
        yield 'too high' => [8];
        yield 'unknown name' => ['verbose'];
        yield 'fraction' => [2.5];
        yield 'null' => [null];
    }

    #[DataProvider('provideInvalidLevels')]
    #[Group('Laminas-7238')]
    public function testRejectsInvalidLevels(mixed $level): void
    {
        $this->subject->addWriter($this->writer);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$level must be an integer >= 0 and < 8; received ');

        $this->subject->log($level, 'Foo');
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function provideTestPlaceholders(): iterable
    {
        $context = [
            'placeholder1'  => 1,
            '#placeholder2' => 'placeholder2',
            'placeholder3'  => new stdClass(),
        ];

        yield 'no placeholders' => ['message with no placeholders', $context];
        yield 'invalid placeholders' => [
            '{placeholder1, {#placeholder2}, place_holder3} invalid placeholders',
            $context,
        ];
        yield 'no context' => ['{placeholder1}, {#placeholder2}, {place_holder3}', []];
        yield 'null value' => ['{nothing}', ['nothing' => null]];
    }

    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('provideTestPlaceholders')]
    public function testLoggingWithoutValidPlaceholdersDoesNotModifyMessage(string $message, array $context): void
    {
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::INFO, $message, $context);

        $this->assertSame($message, $this->lastEvent()['message']);
    }

    public function testLoggingWithValidPlaceholders(): void
    {
        $this->subject->addWriter($this->writer);
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'ID-42';
            }
        };

        $this->subject->log(
            Logger::INFO,
            '{p1}, {place.holder2}, {place_holder3}, {p4}, {p5}, {p6}, {p7}, {p8}, {p9}',
            [
                'p1'            => 1,
                'place.holder2' => 'placeholder2',
                'place_holder3' => new stdClass(),
                'p4'            => ['array' => 'placeholder4'],
                'p5'            => 5.5,
                'p6'            => static fn (): int => 1,
                'p7'            => $stringable,
                'p8'            => new DateTimeImmutable('2026-01-02 03:04:05+00:00'),
                'p9'            => false,
            ]
        );

        $this->assertSame(
            '1, placeholder2, [object stdClass], [array], 5.5, [object Closure], ID-42, '
            . '2026-01-02T03:04:05+00:00, false',
            $this->lastEvent()['message']
        );
    }

    public function testPlaceholderValue(): void
    {
        $this->assertSame('true', Logger::placeholderValue(true));
        $this->assertSame(
            (new DateTimeImmutable('@0'))->format(DateTimeInterface::RFC3339),
            Logger::placeholderValue(new DateTimeImmutable('@0'))
        );
    }

    public function testLoggingStringable(): void
    {
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::INFO, new class implements Stringable {
            public function __toString(): string
            {
                return 'test';
            }
        });

        $this->assertSame('test', $this->lastEvent()['message']);
    }

    public function testAddFilter(): void
    {
        $filter = new RecordingFilter();
        $this->writer->addFilter($filter);
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::INFO, 'test');

        $this->assertCount(1, $filter->events);
        $this->assertSame('test', $filter->events[0]['message']);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function provideTestFilters(): iterable
    {
        yield 'level' => ['level', ['level' => Logger::INFO]];
        yield 'regex' => ['regex', ['regex' => '/[0-9]+/']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideTestFilters')]
    public function testAddFilterByNameWithParams(string $filter, array $options): void
    {
        $this->writer->addFilter($filter, $options);
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::INFO, '123');

        $this->assertSame('123', $this->lastEvent()['message']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>|ArrayObject<string, mixed>}>
     */
    public static function provideAttributes(): iterable
    {
        yield 'empty' => [[]];
        yield 'array' => [['user' => 'foo', 'ip' => '127.0.0.1']];
        yield 'traversable' => [new ArrayObject(['id' => 42])];
    }

    /**
     * @param array<string, mixed>|ArrayObject<string, mixed> $context
     */
    #[DataProvider('provideAttributes')]
    public function testLoggingCustomAttributesForUserContext(array|ArrayObject $context): void
    {
        $this->subject->addWriter($this->writer);

        $this->subject->log(Logger::ERR, 'tottakai', $context);

        $this->assertIsArray($this->lastEvent()['context']);
        $this->assertCount(count($context), $this->lastEvent()['context']);
    }

    /**
     * PHPUnit masks error_reporting() while a test runs, so it is raised to E_ALL around the trigger.
     */
    public function testRegisterErrorHandler(): void
    {
        $this->subject->addWriter($this->writer);

        $previousLevel = error_reporting(E_ALL);
        try {
            $previous = Logger::registerErrorHandler($this->subject);
            $this->assertNotFalse($previous);
            $this->assertFalse(Logger::registerErrorHandler($this->subject), 'registers once');

            trigger_error('dot-log test warning', E_USER_WARNING);
        } finally {
            Logger::unregisterErrorHandler();
            error_reporting($previousLevel);
        }

        $event = $this->lastEvent();
        $this->assertSame('dot-log test warning', $event['message']);
        $this->assertSame(Logger::WARN, $event['level']);
        $this->assertIsArray($event['context']);
        $this->assertSame(E_USER_WARNING, $event['context']['errno'], 'errno is the PHP error number');
        $this->assertSame(__FILE__, $event['context']['file']);
    }

    public function testErrorHandlerSkipsErrorsOutsideErrorReporting(): void
    {
        $this->subject->addWriter($this->writer);
        $handler = Logger::createErrorHandler($this->subject, true);

        $previousLevel = error_reporting(E_ALL & ~E_USER_DEPRECATED);
        try {
            $this->assertFalse($handler(E_USER_DEPRECATED, 'ignored', __FILE__, __LINE__));
            $this->assertFalse($handler(E_USER_NOTICE, 'logged', __FILE__, __LINE__));
        } finally {
            error_reporting($previousLevel);
        }

        $this->assertCount(1, $this->writer->events);
        $this->assertSame(Logger::NOTICE, $this->lastEvent()['level']);
    }

    public function testExceptionHandlerLogsTheChainInnermostFirst(): void
    {
        $this->subject->addWriter($this->writer);
        $handler = Logger::createExceptionHandler($this->subject);

        $handler(new Exception('error', 200, new Exception('previous', 100)));
        $handler(new ErrorException('user notice', 1_000, E_USER_NOTICE, __FILE__, __LINE__));

        $expectedEvents = [
            ['level' => Logger::ERR, 'message' => 'previous'],
            ['level' => Logger::ERR, 'message' => 'error'],
            ['level' => Logger::NOTICE, 'message' => 'user notice'],
        ];
        $this->assertCount(3, $this->writer->events);
        foreach ($expectedEvents as $i => $expected) {
            $event = $this->writer->events[$i];
            $this->assertSame($expected['level'], $event['level']);
            $this->assertSame($expected['message'], $event['message']);
            $this->assertIsArray($event['context']);
            $this->assertSame(__FILE__, $event['context']['file']);
        }
    }

    public function testRegisterExceptionHandlerOnlyOnce(): void
    {
        try {
            $this->assertTrue(Logger::registerExceptionHandler($this->subject));
            $this->assertFalse(Logger::registerExceptionHandler($this->subject));
        } finally {
            Logger::unregisterExceptionHandler();
        }
    }

    public function testOptionsWithWriter(): void
    {
        $logger = new Logger([
            'writers' => [
                'first_writer' => ['name' => 'null', 'level' => 1],
            ],
        ]);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Noop::class, $writers[0]);
    }

    /**
     * Regression test: "level" is documented as optional but omitting it threw a TypeError.
     */
    public function testWriterAndProcessorLevelIsOptional(): void
    {
        $logger = new Logger([
            'writers'    => [['name' => 'noop']],
            'processors' => [['name' => 'requestid']],
        ]);

        $this->assertCount(1, $logger->getWriters());
        $this->assertCount(1, $logger->getProcessors());
    }

    public function testPriorityIsAnAliasOfLevel(): void
    {
        $first   = new MemoryWriter();
        $second  = new MemoryWriter();
        $writers = new WriterPluginManager(null, [
            'factories' => [
                'first'  => static fn (): MemoryWriter => $first,
                'second' => static fn (): MemoryWriter => $second,
            ],
        ]);

        $logger = new Logger([
            'writer_plugin_manager' => $writers,
            'writers'               => [['name' => 'second', 'priority' => 1], ['name' => 'first', 'priority' => 5]],
        ]);

        $this->assertSame([$first, $second], $logger->getWriters()->toArray());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidPluginSpecs(): iterable
    {
        yield 'writer without name' => [['writers' => [['level' => 1]]], 'Options must contain a name for the writer'];
        yield 'writer not an array' => [['writers' => ['noop']], 'Options must contain a name for the writer'];
        yield 'processor without name' => [
            ['processors' => [['level' => 1]]],
            'Options must contain a name for the processor',
        ];
        yield 'non-numeric level' => [
            ['writers' => [['name' => 'noop', 'level' => 'high']]],
            'The writer "noop" level must be an integer; received string',
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideInvalidPluginSpecs')]
    public function testInvalidPluginSpecs(array $options, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Logger($options);
    }

    /**
     * Regression test: options given as a generator were dropped.
     */
    public function testOptionsAsGenerator(): void
    {
        $options = (static function (): iterable {
            yield 'writers' => [['name' => 'noop', 'options' => new ArrayObject([])]];
        })();

        $this->assertCount(1, (new Logger($options))->getWriters());
    }

    public function testOptionsWithWriterOptions(): void
    {
        $logger = new Logger([
            'writers' => [
                [
                    'name'    => 'stream',
                    'options' => ['stream' => 'php://memory', 'log_separator' => 'foo'],
                    'level'   => 1,
                ],
            ],
        ]);

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Stream::class, $writers[0]);
        $this->assertSame('foo', $writers[0]->getLogSeparator());
    }

    public function testOptionsWithWriterAndProcessor(): void
    {
        $logger = new Logger([
            'writers'    => ['first_writer' => ['name' => 'null', 'level' => 1]],
            'processors' => ['first_processor' => ['name' => 'requestid', 'level' => 1]],
        ]);

        $processors = $logger->getProcessors()->toArray();
        $this->assertCount(1, $processors);
        $this->assertInstanceOf(RequestId::class, $processors[0]);
    }

    public function testAddProcessor(): void
    {
        $processor = new Backtrace();
        $this->subject->addProcessor($processor);

        $this->assertSame([$processor], $this->subject->getProcessors()->toArray());
    }

    /**
     * Regression test: Backtrace ignored "Laminas\Log" and reported Dot\Log\Logger::log as the origin.
     */
    public function testBacktraceProcessorReportsTheCaller(): void
    {
        $this->subject->addProcessor('backtrace');
        $this->subject->addWriter($this->writer);

        $this->subject->error('foo');
        $line = __LINE__ - 1;

        $context = $this->lastEvent()['context'];
        $this->assertIsArray($context);
        $this->assertSame(self::class, $context['class']);
        $this->assertSame(__FUNCTION__, $context['function']);
        $this->assertSame(__FILE__, $context['file']);
        $this->assertSame($line, $context['line']);
    }

    public function testSetProcessorPluginManagerByClassName(): void
    {
        $default = $this->subject->getProcessorPluginManager();

        $this->subject->setProcessorPluginManager(ProcessorPluginManager::class);

        $this->assertNotSame($default, $this->subject->getProcessorPluginManager());
    }

    public function testSetProcessorPluginManagerRejectsOtherClasses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Processor plugin manager must extend ' . ProcessorPluginManager::class);

        $this->subject->setProcessorPluginManager(stdClass::class);
    }

    /**
     * Regression test: the destructor iterated the queue itself, which emptied the queue returned by getWriters().
     */
    public function testDestructorKeepsTheWritersQueue(): void
    {
        $logger = new Logger();
        $logger->addWriter($this->writer);
        $writers = $logger->getWriters();
        unset($logger);

        $this->assertCount(1, $writers);
    }

    public function testDestructorShutsWritersDown(): void
    {
        $logger = new Logger();
        $logger->addWriter($this->writer);
        unset($logger);

        $this->assertTrue($this->writer->shutdown);
    }
}
