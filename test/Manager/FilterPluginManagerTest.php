<?php

declare(strict_types=1);

namespace DotTest\Log\Manager;

use Dot\Log\Filter\Priority;
use Dot\Log\Formatter\Json;
use Dot\Log\Manager\FilterPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FilterPluginManagerTest extends TestCase
{
    private FilterPluginManager $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $container     = $this->createMock(ContainerInterface::class);
        $this->subject = new FilterPluginManager($container);
    }

    public function testValidate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->validate(new Priority(47));
    }

    public function testWillNotValidate(): void
    {
        $this->expectExceptionMessage(
            'Dot\Log\Manager\FilterPluginManager can only create instances of'
            . ' Dot\Log\Filter\FilterInterface; Dot\Log\Formatter\Json is invalid'
        );
        $this->expectException(InvalidServiceException::class);
        $this->subject->validate(new Json());
    }
}
