<?php

declare(strict_types=1);

namespace Dot\Log\Processor;

class ReferenceId extends RequestId implements ProcessorInterface
{
    /**
     * Adds an identifier for the request to the log.
     *
     * This enables to filter the log for messages belonging to a specific request
     */
    public function process(array $event): array
    {
        if (isset($event['context']['referenceId'])) {
            return $event;
        }

        if (! isset($event['context'])) {
            $event['context'] = [];
        }

        $event['context']['referenceId'] = $this->getIdentifier();

        return $event;
    }

    public function setReferenceId(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getReferenceId(): string
    {
        return $this->getIdentifier();
    }
}
