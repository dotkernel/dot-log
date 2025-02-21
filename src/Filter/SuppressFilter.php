<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function gettype;
use function is_array;
use function is_bool;
use function iterator_to_array;
use function sprintf;

class SuppressFilter implements FilterInterface
{
    protected bool $accept = true;

    /**
     * This is a simple boolean filter.
     */
    public function __construct(bool|iterable $suppress = false)
    {
        if ($suppress instanceof Traversable) {
            $suppress = iterator_to_array($suppress);
        }
        if (is_array($suppress)) {
            $suppress = $suppress['suppress'] ?? false;
        }
        if (! is_bool($suppress)) {
            throw new InvalidArgumentException(
                sprintf('Suppress must be a boolean; received "%s"', gettype($suppress))
            );
        }

        $this->suppress($suppress);
    }

    /**
     * This is a simple boolean filter.
     *
     * Call suppress(true) to suppress all log events.
     * Call suppress(false) to accept all log events.
     */
    public function suppress(bool $suppress): void
    {
        $this->accept = ! $suppress;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     */
    public function filter(array $event): bool
    {
        return $this->accept;
    }
}
