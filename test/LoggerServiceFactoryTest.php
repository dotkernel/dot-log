<?php

declare(strict_types=1);

namespace DotTest\Log;

use Dot\Log\Logger;
use Dot\Log\LoggerServiceFactory;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Processor\PsrPlaceholder;
use Dot\Log\Writer\Noop;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayObject;
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
        $this->serviceManager = new ServiceManager([
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
     */
    public function testWillInjectWriterPluginManagerIfAvailable(): void
    {
        $writers = new WriterPluginManager(new ServiceManager());

        $services = new ServiceManager([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'LogWriterManager' => $writers,
                'config'           => [
                    'log' => [
                        'writers' => [['name' => 'noop', 'priority' => 1]],
                    ],
                ],
            ],
        ]);

        $log        = $services->get(Logger::class);
        $logWriters = $log->getWriters();
        self::assertEquals(1, count($logWriters));
        $this->assertInstanceOf(Noop::class, $logWriters->current());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillInjectProcessorPluginManagerIfAvailable(): void
    {
        $processors = new ProcessorPluginManager(new ServiceManager());

        $services = new ServiceManager([
            'factories' => [
                Logger::class => LoggerServiceFactory::class,
            ],
            'services'  => [
                'LogProcessorManager' => $processors,
                'config'              => [
                    'log' => [
                        'writers'    => [['name' => Noop::class, 'priority' => 1]],
                        'processors' => [['name' => 'psrplaceholder', 'priority' => 1]],
                    ],
                ],
            ],
        ]);

        $log           = $services->get(Logger::class);
        $logProcessors = $log->getProcessors();
        self::assertEquals(1, count($logProcessors));
        $this->assertInstanceOf(PsrPlaceholder::class, $logProcessors->current());
    }

    /**
     * @dataProvider dataWritersValues()
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWritersValue(mixed $writers, int $count): void
    {
        $services = new ServiceManager([
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
        $services = new ServiceManager([
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
