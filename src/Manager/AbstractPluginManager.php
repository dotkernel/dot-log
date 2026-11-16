<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_key_exists;
use function class_exists;
use function get_debug_type;
use function is_array;
use function is_callable;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * Builds log plugins (writers, filters, formatters, processors) by alias, class name or factory.
 *
 * It does not depend on any container implementation. A PSR-11 container is optional: it is passed to plugin
 * factories, and it is asked for plugins that are neither aliases, factories nor class names.
 *
 * Accepted configuration:
 * - aliases: array<string, string> plugin name => class name or another alias
 * - factories: array<string, class-string|callable> class name => factory.
 *   A factory is called as $factory(ContainerInterface $container, string $requestedName, ?array $options)
 *
 * @template-covariant T of object
 */
abstract class AbstractPluginManager implements ContainerInterface
{
    /** @var array<string, string> */
    protected array $aliases = [];

    /** @var array<string, callable|string> */
    protected array $factories = [];

    /** @var class-string */
    protected string $instanceOf;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(protected ?ContainerInterface $container = null, array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function configure(array $config): static
    {
        if (isset($config['aliases'])) {
            if (! is_array($config['aliases'])) {
                throw new InvalidArgumentException('Plugin manager "aliases" must be an array');
            }
            foreach ($config['aliases'] as $alias => $target) {
                if (! is_string($alias) || ! is_string($target)) {
                    throw new InvalidArgumentException('Plugin manager aliases must map strings to strings');
                }
                $this->aliases[$alias] = $target;
            }
        }

        if (isset($config['factories'])) {
            if (! is_array($config['factories'])) {
                throw new InvalidArgumentException('Plugin manager "factories" must be an array');
            }
            foreach ($config['factories'] as $name => $factory) {
                if (! is_string($name) || (! is_string($factory) && ! is_callable($factory))) {
                    throw new InvalidArgumentException(
                        'Plugin manager factories must map strings to a factory class name or a callable'
                    );
                }
                $this->factories[$name] = $factory;
            }
        }

        return $this;
    }

    /**
     * Build a new plugin instance. Plugins are never shared.
     *
     * @param array<array-key, mixed>|null $options
     * @return T
     * @throws ContainerExceptionInterface
     */
    public function build(string $name, ?array $options = null): object
    {
        $resolved = $this->resolveAlias($name);

        if (array_key_exists($resolved, $this->factories)) {
            $factory = $this->factories[$resolved];
            if (is_string($factory) && ! is_callable($factory)) {
                if (! class_exists($factory)) {
                    throw new InvalidArgumentException(sprintf(
                        'Factory "%s" for plugin "%s" does not exist',
                        $factory,
                        $name
                    ));
                }
                $factory = new $factory();
            }
            if (! is_callable($factory)) {
                throw new InvalidArgumentException(sprintf('Factory for plugin "%s" is not callable', $name));
            }
            $instance = $factory($this->getCreationContext(), $resolved, $options);
        } elseif (class_exists($resolved)) {
            $instance = $options === null ? new $resolved() : new $resolved($options);
        } elseif ($this->container !== null && $this->container->has($name)) {
            $instance = $this->container->get($name);
        } else {
            throw new ServiceNotFoundException(sprintf(
                'Unable to resolve plugin "%s" in %s',
                $name,
                static::class
            ));
        }

        $this->validate($instance);

        return $instance;
    }

    /**
     * @return T
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): object
    {
        return $this->build($id);
    }

    public function has(string $id): bool
    {
        $resolved = $this->resolveAlias($id);

        return array_key_exists($resolved, $this->factories)
            || class_exists($resolved)
            || ($this->container !== null && $this->container->has($id));
    }

    /**
     * Validate the plugin is of the expected type.
     *
     * @phpstan-assert T $instance
     */
    public function validate(mixed $instance): void
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidArgumentException(sprintf(
                '%s can only create instances of %s; %s is invalid',
                static::class,
                $this->instanceOf,
                get_debug_type($instance)
            ));
        }
    }

    /**
     * The container passed to plugin factories: the application container when one was given, otherwise this manager.
     */
    public function getCreationContext(): ContainerInterface
    {
        return $this->container ?? $this;
    }

    protected function resolveAlias(string $name): string
    {
        $seen = [];
        while (true) {
            if (isset($this->aliases[$name])) {
                $target = $this->aliases[$name];
            } elseif (isset($this->aliases[strtolower($name)])) {
                $target = $this->aliases[strtolower($name)];
            } else {
                return $name;
            }

            if (isset($seen[$target])) {
                throw new InvalidArgumentException(sprintf('Circular plugin alias detected for "%s"', $name));
            }
            $seen[$target] = true;
            $name          = $target;
        }
    }
}
