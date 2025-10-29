<?php

declare(strict_types=1);

namespace DotTest\Log\Manager;

use Dot\Log\Filter\Priority;
use Dot\Log\Formatter\Json;
use Dot\Log\Manager\FormatterPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FormatterPluginManagerTest extends TestCase
{
    private FormatterPluginManager $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container     = $this->createMock(ContainerInterface::class);
        $this->subject = new FormatterPluginManager($container);
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->validate(new Json());
    }

    public function testWillNotValidate(): void
    {
        $this->expectExceptionMessage(
            'Dot\Log\Manager\FormatterPluginManager can only create instances of'
            . ' Dot\Log\Formatter\FormatterInterface; Dot\Log\Filter\Priority is invalid'
        );
        $this->expectException(InvalidServiceException::class);
        $this->subject->validate(new Priority(47));
    }
}
