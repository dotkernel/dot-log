<?php

declare(strict_types=1);

namespace Factory;

use Dot\Log\Factory\FormatterPluginManagerFactory;
use Laminas\Log\FormatterPluginManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class FormatterPluginManagerFactoryTest extends TestCase
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
        (new FormatterPluginManagerFactory())($this->container);
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
        (new FormatterPluginManagerFactory())($this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillNotInstantiateWithoutFormatterConfig(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->method('get')->willReturn([
            'dot_log' => [],
        ]);

        $this->expectExceptionMessage('Unable to find formatter_manager config');
        $this->expectException(\Exception::class);
        (new FormatterPluginManagerFactory())($this->container);
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
            'dot_log' => ['formatter_manager' => []],
        ]);

        $factory = (new FormatterPluginManagerFactory())($this->container);

        $this->assertInstanceOf(FormatterPluginManager::class, $factory);
    }
}
