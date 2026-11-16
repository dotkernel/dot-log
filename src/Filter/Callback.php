<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Closure;
use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function get_debug_type;
use function is_callable;
use function iterator_to_array;
use function sprintf;

/**
 * Accepts a log event when the callback returns true.
 */
class Callback implements FilterInterface
{
    protected Closure $callback;

    /**
     * @param callable(array<string, mixed>): bool|iterable<string, mixed> $callback
     */
    public function __construct(callable|iterable $callback)
    {
        if (! is_callable($callback)) {
            if ($callback instanceof Traversable) {
                $callback = iterator_to_array($callback);
            }
            $callback = $callback['callback'] ?? null;
        }

        if (! is_callable($callback)) {
            throw new InvalidArgumentException(sprintf(
                'Callback must be callable; received "%s"',
                get_debug_type($callback)
            ));
        }

        $this->callback = Closure::fromCallable($callback);
    }

    /**
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool
    {
        return (bool) ($this->callback)($event);
    }
}
