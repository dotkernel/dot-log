<?php

declare(strict_types=1);

namespace Dot\Log\Formatter;

use Psr\Log\InvalidArgumentException;
use Traversable;

use function array_key_exists;
use function count;
use function is_array;
use function is_string;
use function iterator_to_array;
use function rtrim;
use function str_replace;
use function strpos;

class Simple extends Base
{
    public const DEFAULT_FORMAT = '%timestamp% %levelName% (%level%): %message% %extra%';

    /**
     * Format specifier for log messages
     */
    protected string $format;

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
     */
    public function format(iterable $event): array|string
    {
        $output = $this->format;

        $event = parent::format($event);
        foreach ($event as $name => $value) {
            if ('extra' === $name && is_array($value) && count($value)) {
                $value = $this->normalize($value);
            } elseif ('extra' === $name) {
                // Don't print an empty array
                $value = '';
            }
            $output = str_replace("%$name%", (string) $value, $output);
        }

        if (
            array_key_exists('extra', $event) && empty($event['extra'])
            && false !== strpos($this->format, '%extra%')
        ) {
            $output = rtrim($output, ' ');
        }
        return $output;
    }
}
