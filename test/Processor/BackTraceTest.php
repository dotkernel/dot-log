<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\Backtrace;
use PHPUnit\Framework\TestCase;

class BackTraceTest extends TestCase
{
    public function testProcessAddsTheOriginToTheContext(): void
    {
        $result = (new Backtrace())->process(['context' => ['user' => 'x']]);

        $this->assertIsArray($result['context']);
        $this->assertSame('x', $result['context']['user']);
        $this->assertArrayHasKey('file', $result['context']);
        $this->assertArrayHasKey('line', $result['context']);
        $this->assertArrayHasKey('class', $result['context']);
        $this->assertArrayHasKey('function', $result['context']);
    }

    public function testIgnoredNamespaces(): void
    {
        $this->assertSame(['Dot\\Log', 'Psr\\Log'], (new Backtrace())->getIgnoredNamespaces());
        $this->assertSame(
            ['Dot\\Log', 'Psr\\Log', 'App\\Logging', 'Other'],
            (new Backtrace(['ignoredNamespaces' => ['App\\Logging', '', 'Other']]))->getIgnoredNamespaces()
        );
        $this->assertSame(
            ['Dot\\Log', 'Psr\\Log', 'App'],
            (new Backtrace(['ignoredNamespaces' => 'App']))->getIgnoredNamespaces()
        );
    }

    public function testIgnoredNamespaceMatchesWholeSegments(): void
    {
        $result = (new Backtrace(['ignoredNamespaces' => ['DotTest\\Log\\Processor\\BackTrace']]))->process([]);

        // "BackTraceTest" is not inside the namespace "BackTrace", so this test is the origin.
        $this->assertIsArray($result['context']);
        $this->assertSame(self::class, $result['context']['class']);
    }
}
