<?php

declare(strict_types=1);

namespace DotTest\Log\Manager;

use Dot\Log\Formatter\Json;
use Dot\Log\Manager\WriterPluginManager;
use Dot\Log\Writer\Noop;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class WriterPluginManagerTest extends TestCase
{
    private WriterPluginManager $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container     = $this->createMock(ContainerInterface::class);
        $this->subject = new WriterPluginManager($container);
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->validate(new Noop());
    }

    public function testWillNotValidate(): void
    {
        $this->expectExceptionMessage(
            'Dot\Log\Manager\WriterPluginManager can only create instances of'
            . ' Dot\Log\Writer\WriterInterface; Dot\Log\Formatter\Json is invalid'
        );
        $this->expectException(InvalidServiceException::class);
        $this->subject->validate(new Json());
    }
}
