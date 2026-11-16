<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\RequestId;
use PHPUnit\Framework\TestCase;

class RequestIdTest extends TestCase
{
    public function testKeepsAnExistingRequestId(): void
    {
        $input = ['context' => ['requestId' => 'something']];

        $this->assertSame($input, (new RequestId())->process($input));
    }

    public function testAddsTheSameIdToEveryEvent(): void
    {
        $processor = new RequestId();

        $first  = $processor->process([]);
        $second = $processor->process(['context' => 'not an array']);

        $this->assertIsArray($first['context']);
        $this->assertIsArray($second['context']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $first['context']['requestId']);
        $this->assertSame($first['context']['requestId'], $second['context']['requestId']);
    }
}
