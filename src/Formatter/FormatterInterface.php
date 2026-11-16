<?php

declare(strict_types=1);

namespace Dot\Log\Formatter;

interface FormatterInterface
{
    /**
     * Default format specifier for DateTime objects is ISO 8601
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    public const DEFAULT_DATETIME_FORMAT = 'c';

    /**
     * Formats a log event into a single line to be written by the writer.
     *
     * @param array<string, mixed> $event
     */
    public function format(array $event): string;

    /**
     * Get the format specifier for DateTime objects
     */
    public function getDateTimeFormat(): string;

    /**
     * Set the format specifier for DateTime objects
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    public function setDateTimeFormat(string $dateTimeFormat): static;
}
