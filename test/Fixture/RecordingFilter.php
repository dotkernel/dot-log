<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

use Dot\Log\Filter\FilterInterface;

/**
 * Accepts every event and records it.
 */
class RecordingFilter implements FilterInterface
{
    /** @var list<array<string, mixed>> */
    public array $events = [];

    /**
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool
    {
        $this->events[] = $event;
        return true;
    }
}
