<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Factory\LoggerAbstractServiceFactory;
use Laminas\Log\Logger;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class LoggerAbstractServiceFactoryTest extends TestCase
{
    protected ContainerInterface|MockObject $container;
    protected LoggerAbstractServiceFactory|MockObject $factory;

    protected array $config = [
        'dot_log' => [
            'loggers' => [
                'test-log' => [],
            ],
        ],
    ];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory   = new LoggerAbstractServiceFactory();
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testWillInstantiate()
    {
        $this->container
            ->method('has')
            ->will($this->onConsecutiveCalls(true, false));

        $this->container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($this->config);

        $factory = (new LoggerAbstractServiceFactory())($this->container, 'dot-log.test-log');

        $this->assertInstanceOf(Logger::class, $factory);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCanCreate(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($this->config);

        $this->assertIsBool($this->factory->canCreate($this->container, ''));
        $this->assertFalse($this->factory->canCreate($this->container, ''));
        $this->assertTrue($this->factory->canCreate($this->container, 'dot-log.test-log'));
    }
}
