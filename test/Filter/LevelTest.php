<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Level;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    private Level $subject;

    public function setUp(): void
    {
        $this->subject = new Level(47);
    }

    public function testWillInstantiateWithInt(): void
    {
        $this->assertSame(Level::class, $this->subject::class);
    }

    public function testWillInstantiateWithArray(): void
    {
        $input = ['level' => 47];

        $result = new Level($input);

        $this->assertSame(Level::class, $result::class);
    }

    public function testWillNotInstantiateWithEmptyArray(): void
    {
        $input = [];

        $this->expectExceptionMessage('Level must be a number, received "NULL"');
        $this->expectException(InvalidArgumentException::class);
        new Level($input);
    }

    public function testFilterWillAcceptMessage(): void
    {
        $input = ['level' => 47];

        $result = $this->subject->filter($input);

        $this->assertTrue($result);
    }

    public function testFilterWillNotAcceptMessage(): void
    {
        $input = ['level' => 244];

        $result = $this->subject->filter($input);

        $this->assertFalse($result);
    }
}
