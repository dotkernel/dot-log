<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use DateTime;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Formatter\Simple;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    private Simple $subject;

    protected function setUp(): void
    {
        $this->subject = new Simple(Simple::DEFAULT_FORMAT);
    }

    public function testWillInstantiate(): void
    {
        $this->assertSame(Simple::class, $this->subject::class);
    }

    public function testWillNotInstantiate(): void
    {
        $this->expectExceptionMessage('Format must be a string');
        $this->expectException(InvalidArgumentException::class);
        new Simple(['format' => new DateTime()]);
    }

    public function testFormat(): void
    {
        $input  = ['message' => 'Test Message', 'levelName' => 'Critical'];
        $result = $this->subject->format($input);
        $this->assertIsString($result);
    }
}
