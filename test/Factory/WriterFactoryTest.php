<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Factory\WriterFactory;
use Dot\Log\Writer\AbstractWriter;
use Dot\Log\Writer\Stream;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class WriterFactoryTest extends TestCase
{
    private ContainerInterface|Stub $container;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testWillInstantiate(): void
    {
        $factory = (new WriterFactory())(
            $this->container,
            Stream::class,
            ['stream' => __DIR__ . '/../../log/dk.log']
        );

        $this->assertInstanceOf(AbstractWriter::class, $factory);
    }
}
