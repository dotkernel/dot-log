<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

use function gettype;
use function is_object;
use function is_scalar;
use function method_exists;
use function strpos;
use function strtr;

class PsrPlaceholder implements ProcessorInterface
{
    public function process(array $event): array
    {
        if (false === strpos($event['message'], '{')) {
            return $event;
        }

        $replacements = [];
        foreach ($event['context'] as $key => $val) {
            if (
                $val === null
                || is_scalar($val)
                || (is_object($val) && method_exists($val, "__toString"))
            ) {
                $replacements['{' . $key . '}'] = $val;
                continue;
            }

            if (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . $val::class . ']';
                continue;
            }

            $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
        }

        $event['message'] = strtr($event['message'], $replacements);
        return $event;
    }
}
