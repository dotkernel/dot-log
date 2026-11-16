<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use Laminas\Stdlib\ErrorHandler;
use Traversable;

use function is_array;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function var_export;

class Regex implements FilterInterface
{
    /**
     * Regex to match
     */
    protected string $regex;

    /**
     * Filter out any log messages not matching the pattern
     *
     * @param string|iterable<string, mixed> $regex a pattern, or options with the key "regex"
     */
    public function __construct(string|iterable $regex)
    {
        if ($regex instanceof Traversable) {
            $regex = iterator_to_array($regex);
        }
        if (is_array($regex)) {
            $regex = $regex['regex'] ?? null;
        }
        if (! is_string($regex) || $regex === '') {
            throw new InvalidArgumentException('Option "regex" must be a non-empty string');
        }

        ErrorHandler::start();
        $result = preg_match($regex, '');
        $error  = ErrorHandler::stop();
        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                'Invalid regular expression "%s"',
                $regex
            ), 0, $error);
        }
        $this->regex = $regex;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param array<string, mixed> $event
     */
    public function filter(array $event): bool
    {
        $message = $event['message'] ?? '';
        if (is_array($message)) {
            $message = var_export($message, true);
        }

        return preg_match($this->regex, is_scalar($message) ? (string) $message : '') > 0;
    }
}
