<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Regex;
use Laminas\Stdlib\ErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegexTest extends TestCase
{
    public function testFiltersByPattern(): void
    {
        $filter = new Regex(['regex' => '/(a)(b)*(c)/']);

        $this->assertTrue($filter->filter(['message' => 'ac']));
        $this->assertFalse($filter->filter(['message' => 'bc']));
    }

    public function testAcceptsAPatternString(): void
    {
        $this->assertTrue((new Regex('/^x/'))->filter(['message' => 'xyz']));
        $this->assertTrue((new Regex(new ArrayObject(['regex' => '/^x/'])))->filter(['message' => 'xyz']));
    }

    public function testArrayMessagesAreExported(): void
    {
        $this->assertTrue((new Regex('/needle/'))->filter(['message' => ['key' => 'needle']]));
    }

    public function testMissingMessageDoesNotMatch(): void
    {
        $this->assertFalse((new Regex('/./'))->filter([]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>|string}>
     */
    public static function provideMissingPatterns(): iterable
    {
        yield 'empty array' => [[]];
        yield 'empty string' => [''];
        yield 'not a string' => [['regex' => 1]];
    }

    /**
     * Regression test: an empty pattern threw a TypeError and left an error handler active.
     *
     * @param array<string, mixed>|string $regex
     */
    #[DataProvider('provideMissingPatterns')]
    public function testRejectsAMissingPattern(array|string $regex): void
    {
        try {
            new Regex($regex);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Option "regex" must be a non-empty string', $exception->getMessage());
        }

        $this->assertFalse(ErrorHandler::started());
    }

    public function testRejectsAnInvalidPattern(): void
    {
        try {
            new Regex(['regex' => '/(unclosed/']);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Invalid regular expression "/(unclosed/"', $exception->getMessage());
            $this->assertNotNull($exception->getPrevious());
        }

        $this->assertFalse(ErrorHandler::started());
    }
}
