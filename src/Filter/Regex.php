<?php

declare(strict_types=1);

namespace Dot\Log\Filter;

use Dot\Log\Exception\InvalidArgumentException;
use ErrorException;
use Laminas\Stdlib\ErrorHandler;
use Traversable;

use function is_array;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function var_export;

class Regex implements FilterInterface
{
    /**
     * Regex to match
     */
    protected mixed $regex;

    /**
     * Filter out any log messages not matching the pattern
     *
     * @throws ErrorException
     */
    public function __construct(iterable $regex)
    {
        if ($regex instanceof Traversable) {
            $regex = iterator_to_array($regex);
        }
        $regex = $regex['regex'] ?? null;
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
     */
    public function filter(array $event): bool
    {
        $message = $event['message'];
        if (is_array($event['message'])) {
            $message = var_export($message, true);
        }
        return preg_match($this->regex, $message) > 0;
    }
}
