<?php

declare(strict_types=1);

namespace Factory;

use Dot\Log\Factory\FilterPluginManagerFactory;
use Laminas\Log\FilterPluginManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class FilterPluginManagerFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillNotInstantiateWithoutConfig(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectExceptionMessage('Unable to find config');
        $this->expectException(\Exception::class);
        (new FilterPluginManagerFactory())($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillNotInstantiateWithoutDotLog(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->method('get')->willReturnMap([]);

        $this->expectExceptionMessage('Unable to find dot_log config');
        $this->expectException(\Exception::class);
        (new FilterPluginManagerFactory())($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillNotInstantiateWithoutFilterConfig(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->method('get')->willReturn([
            'dot_log' => [],
        ]);

        $this->expectExceptionMessage('Unable to find filter_manager config');
        $this->expectException(\Exception::class);
        (new FilterPluginManagerFactory())($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillInstantiate(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->method('get')->willReturn([
            'dot_log' => ['filter_manager' => []],
        ]);

        $factory = (new FilterPluginManagerFactory())($this->container);

        $this->assertInstanceOf(FilterPluginManager::class, $factory);
    }
}
