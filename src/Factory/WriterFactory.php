<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;

class WriterFactory implements FactoryInterface
{
    /**
     * Options to pass to the constructor, if any.
     */
    private ?array $creationOptions = null;

    public function __construct(?array $creationOptions = null)
    {
        if (is_array($creationOptions)) {
            $this->setCreationOptions($creationOptions);
        }
    }

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): object
    {
        $options = (array) $options;

        $options = $this->populateOptions($options, $container, 'filter_manager', 'LogFilterManager');
        $options = $this->populateOptions($options, $container, 'formatter_manager', 'LogFormatterManager');

        return new $requestedName($options);
    }

    /**
     * Populates the options array with the correct container value.
     */
    private function populateOptions(
        array $options,
        ContainerInterface $container,
        string $name,
        string $defaultService
    ): array {
        if (isset($options[$name]) && is_string($options[$name])) {
            $options[$name] = $container->get($options[$name]);
            return $options;
        }

        if (! isset($options[$name]) && $container->has($defaultService)) {
            $options[$name] = $container->get($defaultService);
            return $options;
        }

        return $options;
    }

    public function setCreationOptions(array $creationOptions): void
    {
        $this->creationOptions = $creationOptions;
    }
}
