<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

interface ProcessorInterface
{
    /**
     * Processes a log message before it is given to the writers
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function process(array $event): array;
}
