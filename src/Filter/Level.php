<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function iterator_to_array;
use function sprintf;

class Level implements FilterInterface
{
    public const OPERATORS = ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'];

    protected int $level;

    protected string $operator;

    /**
     * Filter logging by $level. By default, it will accept any log
     * event whose level is less than or equal to $level.
     *
     * @param int|iterable<string, mixed> $level
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

        $operator ??= '<=';
        if (! in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Operator must be one of "%s", received "%s"',
                implode('", "', self::OPERATORS),
                $operator
            ));
        }

        $this->level    = $level;
        $this->operator = $operator;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool
    {
        if (! isset($event['level']) || ! is_numeric($event['level'])) {
            return false;
        }

        $level = (int) $event['level'];

        return match ($this->operator) {
            '<', 'lt'        => $level < $this->level,
            '<=', 'le'       => $level <= $this->level,
            '>', 'gt'        => $level > $this->level,
            '>=', 'ge'       => $level >= $this->level,
            '==', '=', 'eq'  => $level === $this->level,
            default          => $level !== $this->level,
        };
    }
}
