<?php

declare(strict_types=1);

namespace Dot\Log\Formatter;

use Dot\Log\Exception\InvalidArgumentException;
use Traversable;

use function is_array;
use function is_string;
use function iterator_to_array;
use function rtrim;
use function str_contains;
use function str_replace;

class Simple extends AbstractFormatter
{
    public const DEFAULT_FORMAT = '%timestamp% %levelName% (%level%): %message% %extra%';

    /**
     * Format specifier for log messages
     */
    protected string $format;

    /**
     * @param string|iterable<string, mixed>|null $format         a format, or options with the keys "format" and
     *                                                            "dateTimeFormat"
     * @param string|iterable<string, mixed>|null $dateTimeFormat
     */
    public function __construct(iterable|string|null $format = null, string|iterable|null $dateTimeFormat = null)
    {
        if ($format instanceof Traversable) {
            $format = iterator_to_array($format);
        }

        if (is_array($format)) {
            $dateTimeFormat = $format['dateTimeFormat'] ?? null;
            $format         = $format['format'] ?? null;
        }

        if (isset($format) && ! is_string($format)) {
            throw new InvalidArgumentException('Format must be a string');
        }

        $this->format = $format ?? static::DEFAULT_FORMAT;

        parent::__construct($dateTimeFormat);
    }

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array<string, mixed> $event
     */
    public function format(array $event): string
    {
        $output = $this->format;

        // Events from Dot\Log\Logger carry no "extra" key: print nothing rather than a literal "%extra%"
        $event += ['extra' => []];
        $event  = $this->normalizeEvent($event);
        foreach ($event as $name => $value) {
            if ('extra' === $name && is_array($value) && $value !== []) {
                $value = $this->normalize($value);
            } elseif ('extra' === $name) {
                // Don't print an empty array
                $value = '';
            }
            $output = str_replace("%$name%", (string) $value, $output);
        }

        if ($event['extra'] === [] && str_contains($this->format, '%extra%')) {
            $output = rtrim($output, ' ');
        }

        return $output;
    }
}
