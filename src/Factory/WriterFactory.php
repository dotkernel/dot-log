<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Writer\WriterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function class_exists;
use function is_a;
use function is_string;
use function sprintf;

/**
 * Creates writers for the WriterPluginManager. Plugin managers given by service name are fetched from the container.
 */
class WriterFactory
{
    /**
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): WriterInterface {
        if (! class_exists($requestedName) || ! is_a($requestedName, WriterInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not a class implementing %s',
                $requestedName,
                WriterInterface::class
            ));
        }

        $options = (array) $options;
        $options = $this->populateOptions($options, $container, 'filter_manager', 'LogFilterManager');
        $options = $this->populateOptions($options, $container, 'formatter_manager', 'LogFormatterManager');

        return new $requestedName($options);
    }

    /**
     * Populates the options array with the correct container value.
     *
     * @param array<array-key, mixed> $options
     * @return array<array-key, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function populateOptions(
        array $options,
        ContainerInterface $container,
        string $name,
        string $defaultService
    ): array {
        if (isset($options[$name]) && is_string($options[$name]) && $container->has($options[$name])) {
            $options[$name] = $container->get($options[$name]);
            return $options;
        }

        if (! isset($options[$name]) && $container->has($defaultService)) {
            $options[$name] = $container->get($defaultService);
        }

        return $options;
    }
}
