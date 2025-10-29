<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use Dot\Log\Filter\Regex;
use ErrorException;
use PHPUnit\Framework\TestCase;
use TypeError;

class RegexTest extends TestCase
{
    private Regex $subject;

    /**
     * @throws ErrorException
     */
    public function setUp(): void
    {
        $this->subject = new Regex(['regex' => '/(a)(b)*(c)/']);
    }

    public function testWillInstantiate(): void
    {
        $this->assertContainsOnlyInstancesOf(Regex::class, [$this->subject]);
    }

    /**
     * @throws ErrorException
     */
    public function testWillNotInstantiateWithEmptyArray(): void
    {
        $this->expectExceptionMessage(
            'preg_match(): Argument #1 ($pattern) must be of type string, null given'
        );
        $this->expectException(TypeError::class);
        new Regex([]);
    }

    public function testFilterWillAcceptMessage(): void
    {
        $input = ['message' => 'ac'];

        $result = $this->subject->filter($input);

        $this->assertTrue($result);
    }

    public function testFilterWillNotAcceptMessage(): void
    {
        $input = ['message' => 'What a wonderful day to write tests'];

        $result = $this->subject->filter($input);

        $this->assertFalse($result);
    }
}
