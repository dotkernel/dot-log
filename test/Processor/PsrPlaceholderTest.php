<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\PsrPlaceholder;
use PHPUnit\Framework\TestCase;
use stdClass;

class PsrPlaceholderTest extends TestCase
{
    public function testMessageWithoutPlaceholdersIsUnchanged(): void
    {
        $input = ['message' => 'this is a message', 'context' => ['a' => 1]];

        $this->assertSame($input, (new PsrPlaceholder())->process($input));
    }

    public function testEventWithoutContextIsUnchanged(): void
    {
        $input = ['message' => '{a}'];

        $this->assertSame($input, (new PsrPlaceholder())->process($input));
    }

    public function testProcess(): void
    {
        $result = (new PsrPlaceholder())->process([
            'message' => '{null}|{object}|{string}|{array}|{stringable}',
            'context' => [
                'null'       => null,
                'object'     => new stdClass(),
                'string'     => 'text',
                'array'      => [1],
                'stringable' => new class {
                    public function __toString(): string
                    {
                        return 'cast';
                    }
                },
            ],
        ]);

        $this->assertSame('|[object stdClass]|text|[array]|cast', $result['message']);
    }
}
