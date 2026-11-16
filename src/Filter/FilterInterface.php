<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

interface FilterInterface
{
    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool;
}
