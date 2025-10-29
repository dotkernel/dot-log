<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\Backtrace;
use PHPUnit\Framework\TestCase;

class BackTraceTest extends TestCase
{
    private Backtrace $subject;

    protected function setUp(): void
    {
        $this->subject = new Backtrace();
    }

    public function testWillInstantiate(): void
    {
        $this->assertContainsOnlyInstancesOf(Backtrace::class, [$this->subject]);
    }

    public function testProcess(): void
    {
        $result = $this->subject->process([]);
        $this->assertArrayHasKey('extra', $result);
    }

    public function testGetIgnoredNamespaces(): void
    {
        $result = $this->subject->getIgnoredNamespaces();
        $this->assertIsArray($result);
    }
}
