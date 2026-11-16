<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Closure;
use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function get_debug_type;
use function is_array;
use function is_object;
use function iterator_to_array;
use function method_exists;
use function sprintf;

/**
 * Accepts a log event when the validator accepts its message.
 *
 * The validator is any object with an isValid(mixed $value): bool method, for example a
 * Laminas\Validator\ValidatorInterface implementation. dot-log does not require laminas/laminas-validator.
 */
class Validator implements FilterInterface
{
    protected object $validator;

    /** @var Closure(mixed): mixed */
    private Closure $isValid;

    /**
     * @param iterable<string, mixed>|object $validator a validator, or options with the key "validator"
     */
    public function __construct(iterable|object $validator)
    {
        if (! self::isValidator($validator)) {
            if ($validator instanceof Traversable) {
                $validator = iterator_to_array($validator);
            }
            if (is_array($validator)) {
                $validator = $validator['validator'] ?? null;
            }
        }

        if (! is_object($validator) || ! method_exists($validator, 'isValid')) {
            throw new InvalidArgumentException(sprintf(
                'Parameter of type %s is invalid; must be an object with an isValid() method,'
                . ' such as a Laminas\Validator\ValidatorInterface',
                get_debug_type($validator)
            ));
        }

        $this->validator = $validator;
        $this->isValid   = $validator->isValid(...);
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool
    {
        return (bool) ($this->isValid)($event['message'] ?? null);
    }

    private static function isValidator(mixed $value): bool
    {
        return is_object($value) && method_exists($value, 'isValid');
    }
}
