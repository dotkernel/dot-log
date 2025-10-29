<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Priority;
use PHPUnit\Framework\TestCase;

class PriorityTest extends TestCase
{
    private Priority $subject;

    public function setUp(): void
    {
        $this->subject = new Priority(47);
    }

    public function testWillInstantiateWithInt(): void
    {
        $this->assertContainsOnlyInstancesOf(Priority::class, [$this->subject]);
    }

    public function testWillInstantiateWithArray(): void
    {
        $input = ['priority' => 47];

        $result = new Priority($input);

        $this->assertContainsOnlyInstancesOf(Priority::class, [$result]);
    }

    public function testWillNotInstantiateWithEmptyArray(): void
    {
        $input = [];

        $this->expectExceptionMessage('Priority must be a number, received "NULL"');
        $this->expectException(InvalidArgumentException::class);
        new Priority($input);
    }

    public function testFilterWillAcceptMessage(): void
    {
        $input = ['priority' => 47];

        $result = $this->subject->filter($input);

        $this->assertTrue($result);
    }

    public function testFilterWillNotAcceptMessage(): void
    {
        $input = ['priority' => 244];

        $result = $this->subject->filter($input);

        $this->assertFalse($result);
    }
}
