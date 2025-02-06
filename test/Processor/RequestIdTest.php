<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\RequestId;
use PHPUnit\Framework\TestCase;

class RequestIdTest extends TestCase
{
    private RequestId $subject;

    protected function setUp(): void
    {
        $this->subject = new RequestId();
    }

    public function testProcessWithRequestId(): void
    {
        $input = [
            "context" => [
                "requestId" => "something",
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
}
