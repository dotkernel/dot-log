<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Level;
use Dot\Log\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    public function testDefaultOperatorAcceptsLevelsUpToTheLimit(): void
    {
        $filter = new Level(Logger::WARN);

        $this->assertTrue($filter->filter(['level' => Logger::ERR]));
        $this->assertTrue($filter->filter(['level' => Logger::WARN]));
        $this->assertFalse($filter->filter(['level' => Logger::INFO]));
    }

    public function testAcceptsOptions(): void
    {
        $this->assertTrue((new Level(['level' => 4, 'operator' => '==']))->filter(['level' => 4]));
        $this->assertTrue((new Level(new ArrayObject(['level' => 4, 'operator' => 'ne'])))->filter(['level' => 3]));
    }

    /**
     * @return iterable<string, array{string, int, bool}>
     */
    public static function provideOperators(): iterable
    {
        foreach (['<', 'lt'] as $op) {
            yield $op . ' lower' => [$op, 3, true];
            yield $op . ' equal' => [$op, 4, false];
        }
        foreach (['<=', 'le'] as $op) {
            yield $op . ' equal' => [$op, 4, true];
            yield $op . ' higher' => [$op, 5, false];
        }
        foreach (['>', 'gt'] as $op) {
            yield $op . ' higher' => [$op, 5, true];
            yield $op . ' equal' => [$op, 4, false];
        }
        foreach (['>=', 'ge'] as $op) {
            yield $op . ' equal' => [$op, 4, true];
            yield $op . ' lower' => [$op, 3, false];
        }
        foreach (['==', '=', 'eq'] as $op) {
            yield $op . ' equal' => [$op, 4, true];
            yield $op . ' other' => [$op, 3, false];
        }
        foreach (['!=', '<>', 'ne'] as $op) {
            yield $op . ' other' => [$op, 3, true];
            yield $op . ' equal' => [$op, 4, false];
        }
    }

    #[DataProvider('provideOperators')]
    public function testOperators(string $operator, int $eventLevel, bool $accepted): void
    {
        $this->assertSame($accepted, (new Level(4, $operator))->filter(['level' => $eventLevel]));
    }

    public function testRejectsEventsWithoutANumericLevel(): void
    {
        $this->assertFalse((new Level(7))->filter([]));
        $this->assertFalse((new Level(7))->filter(['level' => 'debug']));
    }

    public function testWillNotInstantiateWithEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Level must be a number, received "NULL"');

        new Level([]);
    }

    /**
     * Regression test: an invalid operator was accepted and failed with ValueError on the first log call.
     */
    public function testRejectsAnInvalidOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Operator must be one of "<", "lt", "<=", "le", ">", "gt", ">=", "ge", "==", "=", "eq", "!=", "<>", "ne",'
            . ' received "=<"'
        );

        new Level(['level' => 3, 'operator' => '=<']);
    }
}
