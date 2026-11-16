<?php

declare(strict_types=1);

namespace DotTest\Log\Manager;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\ServiceNotFoundException;
use Dot\Log\Factory\WriterFactory;
use Dot\Log\Filter\Callback;
use Dot\Log\Filter\FilterInterface;
use Dot\Log\Filter\Level;
use Dot\Log\Filter\Regex;
use Dot\Log\Filter\SuppressFilter;
use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Formatter\Json;
use Dot\Log\Formatter\Simple;
use Dot\Log\Manager\AbstractPluginManager;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\Backtrace;
use Dot\Log\Processor\PsrPlaceholder;
use Dot\Log\Processor\ReferenceId;
use Dot\Log\Processor\RequestId;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\Stream;
use DotTest\Log\Fixture\InMemoryContainer;
use DotTest\Log\Fixture\MemoryWriter;
use DotTest\Log\Fixture\RecordingFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class PluginManagerTest extends TestCase
{
    /**
     * @return iterable<string, array{AbstractPluginManager<object>, string, class-string, array<string, mixed>|null}>
     */
    public static function provideBuiltInPlugins(): iterable
    {
        yield 'filter callback' => [
            new FilterPluginManager(),
            'callback',
            Callback::class,
            ['callback' => static fn (): bool => true],
        ];
        yield 'filter level' => [new FilterPluginManager(), 'level', Level::class, ['level' => 3]];
        yield 'filter regex' => [new FilterPluginManager(), 'regex', Regex::class, ['regex' => '/a/']];
        yield 'filter suppress' => [new FilterPluginManager(), 'suppress', SuppressFilter::class, null];
        yield 'filter suppressfilter' => [new FilterPluginManager(), 'suppressfilter', SuppressFilter::class, null];
        yield 'formatter simple' => [new FormatterPluginManager(), 'simple', Simple::class, null];
        yield 'formatter json' => [new FormatterPluginManager(), 'json', Json::class, null];
        yield 'formatter class name' => [new FormatterPluginManager(), Json::class, Json::class, null];
        yield 'processor backtrace' => [new ProcessorPluginManager(), 'backtrace', Backtrace::class, null];
        yield 'processor psrplaceholder' => [
            new ProcessorPluginManager(),
            'psrplaceholder',
            PsrPlaceholder::class,
            null,
        ];
        yield 'processor referenceid' => [new ProcessorPluginManager(), 'referenceid', ReferenceId::class, null];
        yield 'processor requestid' => [new ProcessorPluginManager(), 'requestid', RequestId::class, null];
        yield 'writer noop' => [new WriterPluginManager(), 'noop', Noop::class, null];
        yield 'writer null' => [new WriterPluginManager(), 'null', Noop::class, null];
        yield 'writer laminaslogwriternull' => [new WriterPluginManager(), 'laminaslogwriternull', Noop::class, null];
        yield 'writer stream' => [new WriterPluginManager(), 'stream', Stream::class, ['stream' => 'php://memory']];
    }

    /**
     * @param AbstractPluginManager<object> $manager
     * @param class-string $expected
     * @param array<string, mixed>|null $options
     */
    #[DataProvider('provideBuiltInPlugins')]
    public function testBuildsBuiltInPlugins(
        AbstractPluginManager $manager,
        string $name,
        string $expected,
        ?array $options
    ): void {
        $this->assertTrue($manager->has($name));
        $this->assertInstanceOf($expected, $manager->build($name, $options));
    }

    public function testPluginsAreNotShared(): void
    {
        $manager = new FormatterPluginManager();

        $this->assertNotSame($manager->get('simple'), $manager->get('simple'));
    }

    public function testAliasesAreCaseInsensitiveFallback(): void
    {
        $this->assertInstanceOf(Json::class, (new FormatterPluginManager())->build('JSON'));
    }

    public function testBuildPassesOptionsToTheConstructor(): void
    {
        $formatter = (new FormatterPluginManager())->build('simple', ['format' => '%message%']);

        $this->assertSame('hello', $formatter->format(['message' => 'hello']));
    }

    public function testConfiguredAliasesAndFactoriesAreUsed(): void
    {
        $manager = new FilterPluginManager(null, [
            'aliases'   => ['everything' => RecordingFilter::class, 'all' => 'everything'],
            'factories' => [
                'custom' => static fn (ContainerInterface $container, string $name, ?array $options): FilterInterface
                    => new SuppressFilter((bool) ($options['suppress'] ?? false)),
            ],
        ]);

        $this->assertInstanceOf(RecordingFilter::class, $manager->build('all'));
        $this->assertFalse($manager->build('custom', ['suppress' => true])->filter([]));
    }

    public function testFactoryReceivesTheApplicationContainer(): void
    {
        $container = new InMemoryContainer(['greeting' => 'hello']);
        $manager   = new FormatterPluginManager($container, [
            'factories' => [
                'greeting' => static fn (ContainerInterface $c): FormatterInterface
                    => new Simple((string) $c->get('greeting')),
            ],
        ]);

        $this->assertSame($container, $manager->getCreationContext());
        $this->assertSame('hello', $manager->build('greeting')->format([]));
    }

    public function testWithoutContainerTheManagerIsTheCreationContext(): void
    {
        $manager = new WriterPluginManager();

        $this->assertSame($manager, $manager->getCreationContext());
    }

    public function testFallsBackToTheApplicationContainer(): void
    {
        $writer  = new MemoryWriter();
        $manager = new WriterPluginManager(new InMemoryContainer(['app.writer' => $writer]));

        $this->assertTrue($manager->has('app.writer'));
        $this->assertSame($writer, $manager->build('app.writer'));
    }

    public function testUnknownPluginThrowsNotFound(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Unable to resolve plugin "FileWriter" in ' . WriterPluginManager::class);

        (new WriterPluginManager())->build('FileWriter');
    }

    public function testRejectsPluginsOfTheWrongType(): void
    {
        $manager = new WriterPluginManager(null, ['aliases' => ['json' => Json::class]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            WriterPluginManager::class . ' can only create instances of '
            . 'Dot\Log\Writer\WriterInterface; Dot\Log\Formatter\Json is invalid'
        );

        $manager->build('json');
    }

    public function testCircularAliasesAreRejected(): void
    {
        $manager = new FilterPluginManager(null, ['aliases' => ['a' => 'b', 'b' => 'a']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular plugin alias detected for "a"');

        $manager->build('a');
    }

    public function testMissingFactoryClassIsReported(): void
    {
        $manager = new FilterPluginManager(null, ['factories' => ['x' => 'DotTest\Log\DoesNotExist']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Factory "DotTest\Log\DoesNotExist" for plugin "x" does not exist');

        $manager->build('x');
    }

    public function testFactoryClassNameIsInstantiated(): void
    {
        $manager = new WriterPluginManager(null, [
            'aliases'   => ['memory' => MemoryWriter::class],
            'factories' => [MemoryWriter::class => WriterFactory::class],
        ]);

        $this->assertInstanceOf(MemoryWriter::class, $manager->build('memory'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidConfig(): iterable
    {
        yield 'aliases not an array' => [['aliases' => 'x'], 'Plugin manager "aliases" must be an array'];
        yield 'alias target not a string' => [
            ['aliases' => ['x' => 1]],
            'Plugin manager aliases must map strings to strings',
        ];
        yield 'factories not an array' => [['factories' => 'x'], 'Plugin manager "factories" must be an array'];
        yield 'factory not callable' => [
            ['factories' => ['x' => new stdClass()]],
            'Plugin manager factories must map strings to a factory class name or a callable',
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('provideInvalidConfig')]
    public function testRejectsInvalidConfig(array $config, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new FilterPluginManager(null, $config);
    }

    public function testWriterManagerSharesItsFilterAndFormatterManagers(): void
    {
        $filters    = new FilterPluginManager(null, ['aliases' => ['recording' => RecordingFilter::class]]);
        $formatters = new FormatterPluginManager(null, ['aliases' => ['short' => Simple::class]]);
        $manager    = new WriterPluginManager(null, [], $filters, $formatters);

        $writer = $manager->build('noop');

        $this->assertInstanceOf(Noop::class, $writer);
        $this->assertSame($filters, $writer->getFilterPluginManager());
        $this->assertSame($formatters, $writer->getFormatterPluginManager());
    }
}
