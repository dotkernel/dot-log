<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\ReferenceId;
use PHPUnit\Framework\TestCase;

class ReferenceIdTest extends TestCase
{
    private ReferenceId $subject;

    protected function setUp(): void
    {
        $this->subject = new ReferenceId();
    }

    public function testProcessWithReferenceId(): void
    {
        $input = [
            "context" => [
                "referenceId" => "something",
            ],
        ];

        $result = $this->subject->process($input);
        $this->assertSame($input, $result);
    }

    public function testProcess(): void
    {
        $input = [];

        $result = $this->subject->process($input);
        $this->assertArrayHasKey("context", $result);
    }

    public function testGetReferenceId(): void
    {
        $result = $this->subject->getReferenceId();
        $this->assertIsString($result);
    }

    public function testSetReferenceId(): void
    {
        $input  = "something";
        $result = $this->subject->setReferenceId($input);
        $this->assertSame($input, $result->getReferenceId());
    }
}
