<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\Validator;
use Dot\Log\Manager\FilterPluginManager;
use DotTest\Log\Fixture\NotEmptyValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidatorTest extends TestCase
{
    public function testFiltersWithTheValidator(): void
    {
        $validator = new NotEmptyValidator();
        $filter    = new Validator(['validator' => $validator]);

        $this->assertTrue($filter->filter(['message' => 'What a wonderful day to write tests']));
        $this->assertFalse($filter->filter(['message' => '']));
        $this->assertSame(['What a wonderful day to write tests', ''], $validator->values);
    }

    public function testAcceptsAValidatorInstanceOrTraversableOptions(): void
    {
        $this->assertFalse((new Validator(new NotEmptyValidator()))->filter([]));
        $this->assertTrue(
            (new Validator(new ArrayObject(['validator' => new NotEmptyValidator()])))->filter(['message' => 'x'])
        );
    }

    public function testNonBooleanResultsAreCast(): void
    {
        $validator = new class {
            public function isValid(mixed $value): int
            {
                return $value === 'ok' ? 1 : 0;
            }
        };

        $this->assertTrue((new Validator($validator))->filter(['message' => 'ok']));
        $this->assertFalse((new Validator($validator))->filter(['message' => 'no']));
    }

    public function testBuiltByThePluginManager(): void
    {
        $filter = (new FilterPluginManager())->build('validator', ['validator' => new NotEmptyValidator()]);

        $this->assertTrue($filter->filter(['message' => 'x']));
    }

    /**
     * @return iterable<string, array{array<string, mixed>|object, string}>
     */
    public static function provideInvalidValidators(): iterable
    {
        yield 'empty options' => [[], 'null'];
        yield 'object without isValid()' => [['validator' => new stdClass()], 'stdClass'];
        yield 'object given directly' => [new stdClass(), 'stdClass'];
    }

    /**
     * @param array<string, mixed>|object $validator
     */
    #[DataProvider('provideInvalidValidators')]
    public function testRejectsInvalidValidators(array|object $validator, string $type): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Parameter of type ' . $type . ' is invalid; must be an object with an isValid() method,'
            . ' such as a Laminas\Validator\ValidatorInterface'
        );

        new Validator($validator);
    }
}
