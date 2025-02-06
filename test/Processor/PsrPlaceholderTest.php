<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\PsrPlaceholder;
use PHPUnit\Framework\TestCase;

class PsrPlaceholderTest extends TestCase
{
    private PsrPlaceholder $subject;

    protected function setUp(): void
    {
        $this->subject = new PsrPlaceholder();
    }

    public function testProcessMessageWithNoCurlyBrackets(): void
    {
        $input = ["message" => "this is a message"];

        $result = $this->subject->process($input);
        $this->assertSame($input, $result);
    }

    public function testProcess(): void
    {
        $input = [
            "message" => '{nullvalue} of {objectvalue} is in fact a {stringvalue}',
            "context" => [
                "nullvalue"   => null,
                "objectvalue" => $this->subject,
                "stringvalue" => "this is a message",
            ],
        ];

        $result = $this->subject->process($input);
        $this->assertNotSame($input['message'], $result['message']);
    }
}
