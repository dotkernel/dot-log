<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

use Psr\Container\ContainerInterface;
use Throwable;

use function array_key_exists;
use function is_callable;
use function is_string;
use function sprintf;

/**
 * Minimal PSR-11 container: fixed services, plus factories called as $factory($container, $id).
 */
final class InMemoryContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     * @param array<string, callable|class-string> $factories
     */
    public function __construct(
        private array $services = [],
        private readonly array $factories = [],
    ) {
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        if (! array_key_exists($id, $this->factories)) {
            throw new NotFoundException(sprintf('Service "%s" not found', $id));
        }

        $factory = $this->factories[$id];
        try {
            if (is_string($factory) && ! is_callable($factory)) {
                $factory = new $factory();
            }
            if (! is_callable($factory)) {
                throw new ContainerException(sprintf('Factory for "%s" is not callable', $id));
            }
            $service = $factory($this, $id);
        } catch (Throwable $exception) {
            throw new ContainerException(sprintf('Service "%s" could not be created', $id), 0, $exception);
        }

        return $this->services[$id] = $service;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services) || array_key_exists($id, $this->factories);
    }
}
