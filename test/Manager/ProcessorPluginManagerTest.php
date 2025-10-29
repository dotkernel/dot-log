<?php

declare(strict_types=1);

namespace DotTest\Log\Manager;

use Dot\Log\Formatter\Json;
use Dot\Log\Manager\ProcessorPluginManager;
use Dot\Log\Processor\Backtrace;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProcessorPluginManagerTest extends TestCase
{
    private ProcessorPluginManager $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container     = $this->createMock(ContainerInterface::class);
        $this->subject = new ProcessorPluginManager($container);
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->validate(new Backtrace());
    }

    public function testWillNotValidate(): void
    {
        $this->expectExceptionMessage(
            'Dot\Log\Manager\ProcessorPluginManager can only create instances of '
            . 'Dot\Log\Processor\ProcessorInterface; Dot\Log\Formatter\Json is invalid'
        );
        $this->expectException(InvalidServiceException::class);
        $this->subject->validate(new Json());
    }
}
