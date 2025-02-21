<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function gettype;
use function is_array;
use function is_int;
use function iterator_to_array;
use function sprintf;
use function version_compare;

class Level implements FilterInterface
{
    protected int $level;

    protected string $operator;

    /**
     * Filter logging by $level. By default, it will accept any log
     * event whose level is less than or equal to $level.
     */
    public function __construct(iterable|int $level, ?string $operator = null)
    {
        if ($level instanceof Traversable) {
            $level = iterator_to_array($level);
        }
        if (is_array($level)) {
            $operator = $level['operator'] ?? null;
            $level    = $level['level'] ?? null;
        }
        if (! is_int($level)) {
            throw new InvalidArgumentException(sprintf(
                'Level must be a number, received "%s"',
                gettype($level)
            ));
        }

        $this->level    = $level;
        $this->operator = $operator ?? '<=';
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     */
    public function filter(array $event): bool|int|null
    {
        return version_compare((string) $event['level'], (string) $this->level, $this->operator);
    }
}
