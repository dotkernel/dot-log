<?php

declare(strict_types=1);

namespace Dot\Log\Formatter;

use DateTimeInterface;
use Stringable;
use Traversable;

use function get_resource_type;
use function gettype;
use function is_array;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function sprintf;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Base class for formatters: turns every non-scalar value of a log event into a string.
 */
abstract class AbstractFormatter implements FormatterInterface
{
    protected const JSON_FLAGS = JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Format specifier for DateTime objects in event data (default: ISO 8601)
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    protected string $dateTimeFormat = self::DEFAULT_DATETIME_FORMAT;

    /**
     * @see https://www.php.net/manual/en/datetime.format.php
     *
     * @param string|iterable<string, mixed>|null $dateTimeFormat
     */
    public function __construct(string|iterable|null $dateTimeFormat = null)
    {
        if ($dateTimeFormat instanceof Traversable) {
            $dateTimeFormat = iterator_to_array($dateTimeFormat);
        }

        if (is_array($dateTimeFormat)) {
            $dateTimeFormat = $dateTimeFormat['dateTimeFormat'] ?? null;
        }

        if (is_string($dateTimeFormat)) {
            $this->dateTimeFormat = $dateTimeFormat;
        }
    }

    /**
     * Normalizes every value of the event. The "extra" key is kept as an array.
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function normalizeEvent(array $event): array
    {
        foreach ($event as $key => $value) {
            if ('extra' === $key && is_array($value)) {
                $event[$key] = $this->normalizeEvent($value);
            } else {
                $event[$key] = $this->normalize($value);
            }
        }

        return $event;
    }

    /**
     * Normalize all non-scalar data types (except null) into a string value
     */
    protected function normalize(mixed $value): bool|float|int|string|null
    {
        if (is_scalar($value) || null === $value) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($this->getDateTimeFormat());
        }

        if ($value instanceof Traversable) {
            return (string) json_encode(iterator_to_array($value), static::JSON_FLAGS);
        }

        if (is_array($value)) {
            return (string) json_encode($value, static::JSON_FLAGS);
        }

        if (is_resource($value)) {
            return sprintf('resource(%s)', get_resource_type($value));
        }

        if (is_object($value)) {
            if ($value instanceof Stringable) {
                return (string) $value;
            }

            return sprintf('object(%s) %s', $value::class, (string) json_encode($value, static::JSON_FLAGS));
        }

        return gettype($value);
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
