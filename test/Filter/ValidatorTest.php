<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use Dot\Log\Filter\Validator;
use InvalidArgumentException;
use Laminas\Validator\NotEmpty;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $subject;

    protected function setUp(): void
    {
        $this->subject = new Validator(['validator' => new NotEmpty()]);
    }

    public function testWillInstantiate(): void
    {
        $this->assertSame(Validator::class, $this->subject::class);
    }

    public function testWillNotInstantiateWithEmptyArray(): void
    {
        $this->expectExceptionMessage(
            'Parameter of type NULL is invalid; must implement Laminas\Validator\ValidatorInterface'
        );
        $this->expectException(InvalidArgumentException::class);
        new Validator([]);
    }

    public function testFilterWillAcceptMessage(): void
    {
        $input = ['message' => 'What a wonderful day to write tests'];

        $result = $this->subject->filter($input);

        $this->assertTrue($result);
    }

    public function testFilterWillNotAcceptMessage(): void
    {
        $input = ['message' => ''];

        $result = $this->subject->filter($input);

        $this->assertFalse($result);
    }
}
