<?php

declare(strict_types=1);

namespace DotTest\Log\Factory;

use Dot\Log\Factory\WriterFactory;
use Dot\Log\Writer\AbstractWriter;
use Dot\Log\Writer\Stream;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class WriterFactoryTest extends TestCase
{
    private ContainerInterface|MockObject $container;

    private WriterFactory $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->subject   = new WriterFactory();
    }

    public function testWillInstantiate(): void
    {
        $factory = (new WriterFactory())(
            $this->container,
            Stream::class,
            ['stream' => __DIR__ . '/../../log/dk.log']
        );

        $this->assertInstanceOf(AbstractWriter::class, $factory);
    }

    public function testSetCreationOptions(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->setCreationOptions([]);
    }
}
