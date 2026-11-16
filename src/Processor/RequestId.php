<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

use function is_array;
use function is_scalar;
use function is_string;
use function md5;
use function microtime;

class RequestId implements ProcessorInterface
{
    protected ?string $identifier = null;

    /**
     * Adds an identifier for the request to the log, unless one has already been set.
     *
     * This enables to filter the log for messages belonging to a specific request
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function process(array $event): array
    {
        if (isset($event['context']['requestId'])) {
            return $event;
        }

        if (! isset($event['context']) || ! is_array($event['context'])) {
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
        if ($this->identifier !== null && $this->identifier !== '') {
            return $this->identifier;
        }

        $requestTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        $identifier  = is_scalar($requestTime) ? (string) $requestTime : (string) microtime(true);

        $client = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (is_string($client)) {
            $identifier .= $client;
        }

        $this->identifier = md5($identifier);

        return $this->identifier;
    }
}
