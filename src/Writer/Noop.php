<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

class Noop extends AbstractWriter
{
    /**
     * Write a message to the log.
     *
     * @param array<string, mixed> $event
     */
    protected function doWrite(array $event): void
    {
    }
}
