<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Callback;
use Dot\Log\Logger;
use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase
{
    public function testUsesTheCallbackResult(): void
    {
        $filter = new Callback(static fn (array $event): bool => $event['level'] === Logger::ERR);

        $this->assertTrue($filter->filter(['level' => Logger::ERR]));
        $this->assertFalse($filter->filter(['level' => Logger::INFO]));
    }

    public function testAcceptsOptions(): void
    {
        $this->assertTrue((new Callback(['callback' => static fn (): bool => true]))->filter([]));
        $this->assertFalse((new Callback(new ArrayObject(['callback' => static fn (): bool => false])))->filter([]));
    }

    public function testRejectsAMissingCallback(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Callback must be callable; received "null"');

        new Callback([]);
    }
}
