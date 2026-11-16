<?php

declare(strict_types=1);

namespace Dot\Log\Formatter;

use DateTimeInterface;

use function array_walk_recursive;
use function json_encode;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Json implements FormatterInterface
{
    public const JSON_FLAGS = JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Format specifier for DateTime objects in event data (default: ISO 8601)
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    protected string $dateTimeFormat = self::DEFAULT_DATETIME_FORMAT;

    /**
     * Formats data into a single line of JSON. Dates anywhere in the event use the configured date format.
     *
     * @param array<string, mixed> $event
     */
    public function format(array $event): string
    {
        $dateTimeFormat = $this->getDateTimeFormat();
        array_walk_recursive($event, static function (mixed &$value) use ($dateTimeFormat): void {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format($dateTimeFormat);
            }
        });

        return (string) json_encode($event, self::JSON_FLAGS);
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function setDateTimeFormat(string $dateTimeFormat): static
    {
        $this->dateTimeFormat = $dateTimeFormat;
        return $this;
    }
}
