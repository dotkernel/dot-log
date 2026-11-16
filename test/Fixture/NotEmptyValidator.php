<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

/**
 * Has the same isValid() method as a Laminas validator, without depending on laminas/laminas-validator.
 */
final class NotEmptyValidator
{
    /** @var list<mixed> */
    public array $values = [];

    public function isValid(mixed $value): bool
    {
        $this->values[] = $value;

        return $value !== null && $value !== '';
    }
}
