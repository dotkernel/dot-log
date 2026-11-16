<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

use Dot\Log\Filter\FilterInterface;
use Dot\Log\Formatter\FormatterInterface;

interface WriterInterface
{
    /**
     * Add a log filter to the writer
     */
    public function addFilter(FilterInterface|int|string $filter): WriterInterface;

    /**
     * Set a message formatter for the writer
     */
    public function setFormatter(FormatterInterface|string $formatter): WriterInterface;

    /**
     * Write a log message
     *
     * @param array<string, mixed> $event
     */
    public function write(array $event): void;

    /**
     * Perform shutdown activities
     */
    public function shutdown(): void;
}
