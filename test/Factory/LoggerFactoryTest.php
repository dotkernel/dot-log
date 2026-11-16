<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Exception\RuntimeException;
use Dot\Log\Factory\LoggerFactory;
use Dot\Log\Filter\SuppressFilter;
use Dot\Log\Formatter\Json;
use Dot\Log\Logger;
use Dot\Log\Writer\Noop;
use DotTest\Log\Fixture\InMemoryContainer;
use DotTest\Log\Fixture\MemoryWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
use function date;
use function file_get_contents;
use function glob;
use function is_dir;
use function json_decode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function unlink;

class LoggerFactoryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dot-log-test-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory . '/*') ?: []);
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    /**
     * @param array<string, mixed> $loggers
     * @param array<string, mixed> $dotLog
     */
    private function container(array $loggers, array $dotLog = []): InMemoryContainer
    {
        return new InMemoryContainer([
            'config' => ['dot_log' => ['loggers' => $loggers] + $dotLog],
        ]);
    }

    public function testCreatesTheNamedLogger(): void
    {
        $container = $this->container(['app' => ['writers' => [['name' => 'noop']]]]);

        $logger = (new LoggerFactory())($container, 'dot-log.app');

        $writers = $logger->getWriters()->toArray();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(Noop::class, $writers[0]);
    }

    public function testLoggerWithoutWriters(): void
    {
        $logger = (new LoggerFactory())($this->container(['empty' => []]), 'dot-log.empty');

        $this->assertCount(0, $logger->getWriters());
    }

    /**
     * Regression tests: the abstract factory threw or returned true for these cases.
     *
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function provideMissingConfig(): iterable
    {
        yield 'no config service' => [[], 'dot-log.app', 'Unable to find the "dot_log.loggers" config'];
        yield 'no dot_log key' => [['config' => []], 'dot-log.app', 'Unable to find the "dot_log.loggers" config'];
        yield 'no loggers key' => [
            ['config' => ['dot_log' => ['formatter_manager' => []]]],
            'dot-log.formatter_manager',
            'Unable to find the "dot_log.loggers" config',
        ];
        yield 'unknown logger' => [
            ['config' => ['dot_log' => ['loggers' => ['app' => []]]]],
            'dot-log.other',
            'Unable to find the "dot_log.loggers.other" config',
        ];
        yield 'logger config not an array' => [
            ['config' => ['dot_log' => ['loggers' => ['app' => 'x']]]],
            'dot-log.app',
            'Unable to find the "dot_log.loggers.app" config',
        ];
        yield 'missing prefix' => [
            ['config' => ['dot_log' => ['loggers' => ['app' => []]]]],
            'app',
            'LoggerFactory creates services named "dot-log.<logger name>"; "app" was requested',
        ];
        yield 'empty name' => [
            ['config' => ['dot_log' => ['loggers' => ['app' => []]]]],
            'dot-log.',
            'LoggerFactory creates services named "dot-log.<logger name>"; "dot-log." was requested',
        ];
    }

    /**
     * @param array<string, mixed> $services
     */
    #[DataProvider('provideMissingConfig')]
    public function testMissingConfigIsReported(array $services, string $requestedName, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new LoggerFactory())(new InMemoryContainer($services), $requestedName);
    }

    public function testResolvesDatePlaceholdersInStreams(): void
    {
        $container = $this->container([
            'app' => [
                'writers' => [
                    [
                        'name'    => 'stream',
                        'options' => ['stream' => $this->directory . '/app-{Y}-{m}-{d}.log'],
                    ],
                ],
            ],
        ]);

        (new LoggerFactory())($container, 'dot-log.app')->info('dated');

        $file = $this->directory . '/app-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $this->assertStringContainsString('dated', (string) file_get_contents($file));
    }

    public function testParseVariables(): void
    {
        $this->assertSame('log/' . date('Y') . '-x-{}.log', LoggerFactory::parseVariables('log/{Y}-x-{}.log'));
    }

    /**
     * Regression test: dot_log.*_manager config was ignored.
     */
    public function testUsesCustomPluginsFromTheManagerConfig(): void
    {
        $container = $this->container(
            [
                'app' => [
                    'writers' => [
                        [
                            'name'    => 'memory',
                            'options' => ['filters' => [['name' => 'block']], 'formatter' => 'custom_json'],
                        ],
                    ],
                ],
            ],
            [
                'writer_manager'    => ['aliases' => ['memory' => MemoryWriter::class]],
                'filter_manager'    => [
                    'factories' => ['block' => static fn (): SuppressFilter => new SuppressFilter(true)],
                ],
                'formatter_manager' => ['aliases' => ['custom_json' => Json::class]],
            ]
        );

        $logger = (new LoggerFactory())($container, 'dot-log.app');
        $writer = $logger->getWriters()->toArray()[0];
        $logger->info('blocked');

        $this->assertInstanceOf(MemoryWriter::class, $writer);
        $this->assertSame([], $writer->events);
    }

    /**
     * The documented configuration (docs/book/v6/configuring-writer.md) must work as written.
     */
    public function testDocumentedWriterConfiguration(): void
    {
        $container = $this->container([
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name'    => 'stream',
                        'level'   => Logger::ALERT,
                        'options' => [
                            'stream'       => $this->directory . '/dk.log',
                            'filters'      => [
                                'allMessages' => [
                                    'name'    => 'level',
                                    'options' => ['operator' => '>=', 'level' => Logger::EMERG],
                                ],
                            ],
                            'formatter'    => ['name' => 'json'],
                            'log_lifetime' => null,
                        ],
                    ],
                ],
            ],
        ]);

        (new LoggerFactory())($container, 'dot-log.my_logger')->warning('user {id} logged in', ['id' => 7]);

        $line = json_decode(trim((string) file_get_contents($this->directory . '/dk.log')), true);
        $this->assertIsArray($line);
        $this->assertSame('user 7 logged in', $line['message']);
        $this->assertSame(Logger::WARN, $line['level']);
        $this->assertSame('warning', $line['levelName']);
    }

    /**
     * Integration: the factory works with a minimal PSR-11 container.
     */
    public function testWorksWithAMinimalPsr11Container(): void
    {
        $container = new InMemoryContainer(
            ['config' => ['dot_log' => ['loggers' => ['app' => ['writers' => [['name' => 'noop']]]]]]],
            ['dot-log.app' => LoggerFactory::class]
        );

        $this->assertInstanceOf(Logger::class, $container->get('dot-log.app'));
    }
}
