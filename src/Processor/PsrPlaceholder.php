<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

use Stringable;

use function get_debug_type;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function str_contains;
use function strtr;

class PsrPlaceholder implements ProcessorInterface
{
    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function process(array $event): array
    {
        if (
            ! isset($event['message'])
            || ! is_string($event['message'])
            || ! str_contains($event['message'], '{')
            || ! isset($event['context'])
            || ! is_array($event['context'])
        ) {
            return $event;
        }

        $replacements = [];
        foreach ($event['context'] as $key => $val) {
            if ($val === null || is_scalar($val) || $val instanceof Stringable) {
                $replacements['{' . $key . '}'] = (string) $val;
                continue;
            }

            if (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . $val::class . ']';
                continue;
            }

            $replacements['{' . $key . '}'] = '[' . get_debug_type($val) . ']';
        }

        $event['message'] = strtr($event['message'], $replacements);
        return $event;
    }
}
