<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

use Dot\Log\Writer\AbstractWriter;

/**
 * Keeps every written event in memory.
 */
class MemoryWriter extends AbstractWriter
{
    /** @var list<array<string, mixed>> */
    public array $events = [];

    public bool $shutdown = false;

    /**
     * @param array<string, mixed> $event
     */
    public function doWrite(array $event): void
    {
        $this->events[] = $event;
    }

    public function shutdown(): void
    {
        $this->shutdown = true;
    }
}
