<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

use function md5;

class RequestId implements ProcessorInterface
{
    protected ?string $identifier = null;

    /**
     * Adds an identifier for the request to the log, unless one has already been set.
     *
     * This enables to filter the log for messages belonging to a specific request
     */
    public function process(array $event): array
    {
        if (isset($event['context']['requestId'])) {
            return $event;
        }

        if (! isset($event['context'])) {
            $event['context'] = [];
        }

        $event['context']['requestId'] = $this->getIdentifier();
        return $event;
    }

    /**
     * Provide unique identifier for a request
     */
    protected function getIdentifier(): string
    {
        if ($this->identifier) {
            return $this->identifier;
        }

        $identifier = (string) $_SERVER['REQUEST_TIME_FLOAT'];

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $identifier .= $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $identifier .= $_SERVER['REMOTE_ADDR'];
        }

        $this->identifier = md5($identifier);

        return $this->identifier;
    }
}
