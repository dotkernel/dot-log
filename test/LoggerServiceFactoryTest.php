<?php

declare(strict_types=1);

namespace DotTest\Log;

use Dot\Log\Logger;
use Dot\Log\LoggerServiceFactory;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\ProcessorInterface;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\WriterInterface;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayObject;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

use function count;

class LoggerServiceFactoryTest extends TestCase
{
    protected ServiceLocatorInterface $serviceManager;

    /**
     * Set up LoggerServiceFactory and loggers configuration.
     */
    protected function setUp(): void
    {
        $this->serviceManager = new ServiceManager();
        $config               = new Config([
            'aliases'   => [
                'Dot\Log' => Logger::class,
            ],
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'config' => [
                    'log' => [],
                ],
            ],
        ]);
        $config->configureServiceManager($this->serviceManager);
    }

    public static function providerValidLoggerService(): array
    {
        return [
            [Logger::class],
            ['Dot\Log'],
        ];
    }

    public static function providerInvalidLoggerService(): array
    {
        return [
            ['log'],
            ['Logger\Application\Frontend'],
            ['writers'],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @dataProvider providerValidLoggerService
     */
    public function testValidLoggerService(string $service): void
    {
        $actual = $this->serviceManager->get($service);
        self::assertInstanceOf(Logger::class, $actual);
    }

    /**
     * @dataProvider providerInvalidLoggerService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInvalidLoggerService(string $service): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->serviceManager->get($service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testWillInjectWriterPluginManagerIfAvailable(): void
    {
        $writers    = new WriterPluginManager(new ServiceManager());
        $mockWriter = $this->createMock(WriterInterface::class);
        $writers->setService('CustomWriter', $mockWriter);

        $config   = new Config([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'LogWriterManager' => $writers,
                'config'           => [
                    'log' => [
                        'writers' => [['name' => 'CustomWriter', 'priority' => 1]],
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $log        = $services->get(Logger::class);
        $logWriters = $log->getWriters();
        self::assertEquals(1, count($logWriters));
        $writer = $logWriters->current();
        self::assertSame($mockWriter, $writer);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testWillInjectProcessorPluginManagerIfAvailable(): void
    {
        $processors    = new ProcessorPluginManager(new ServiceManager());
        $mockProcessor = $this->createMock(ProcessorInterface::class);
        $processors->setService('CustomProcessor', $mockProcessor);

        $config   = new Config([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'LogProcessorManager' => $processors,
                'config'              => [
                    'log' => [
                        'writers'    => [['name' => Noop::class, 'priority' => 1]],
                        'processors' => [['name' => 'CustomProcessor', 'priority' => 1]],
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $log           = $services->get(Logger::class);
        $logProcessors = $log->getProcessors();
        self::assertEquals(1, count($logProcessors));
        $processor = $logProcessors->current();
        self::assertSame($mockProcessor, $processor);
    }

    /**
     * @dataProvider dataWritersValues()
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWritersValue(mixed $writers, int $count): void
    {
        $config   = new Config([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'config' => [
                    'log' => [
                        'writers' => $writers,
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        /** @var Logger $log */
        $log = $services->get(Logger::class);
        self::assertCount($count, $log->getWriters());
    }

    public static function dataWritersValues(): array
    {
        return [
            'null'           => [null, 0],
            'string'         => ['writers config', 0],
            'number'         => [1e3, 0],
            'object'         => [new stdClass(), 0],
            'empty iterable' => [new ArrayObject(), 0],
            'iterable'       => [new ArrayObject([['name' => Noop::class, 'priority' => 1]]), 1],
        ];
    }

    /**
     * @dataProvider dataInvalidWriterConfig()
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInvalidWriterConfig(mixed $value, string $type): void
    {
        $config   = new Config([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'config' => [
                    'log' => [
                        'writers' => [
                            $value,
                        ],
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        self::expectException(ServiceNotCreatedException::class);
        self::expectExceptionMessage(
            'config log.writers[] must contain array or ArrayAccess, ' . $type . ' provided'
        );

        $services->get(Logger::class);
    }

    public static function dataInvalidWriterConfig(): array
    {
        return [
            'string' => ['invalid config', 'string'],
            'object' => [new stdClass(), 'stdClass'],
        ];
    }
}
