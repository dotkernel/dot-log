<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Filter\FilterInterface;
use Dot\Log\Filter\Priority;
use Dot\Log\Filter\Regex;
use Dot\Log\Filter\SuppressFilter;
use Dot\Log\Filter\Validator;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

use function gettype;
use function is_object;
use function sprintf;

/**
 * @template F of FilterPluginManager
 * @extends AbstractPluginManager<F>
 */
class FilterPluginManager extends AbstractPluginManager
{
    /** @var string[] */
    protected array $aliases = [
        'priority'       => Priority::class,
        'regex'          => Regex::class,
        'suppress'       => SuppressFilter::class,
        'suppressfilter' => SuppressFilter::class,
        'validator'      => Validator::class,
    ];

    protected array $factories = [
        Priority::class       => InvokableFactory::class,
        Regex::class          => InvokableFactory::class,
        SuppressFilter::class => InvokableFactory::class,
        Validator::class      => InvokableFactory::class,
    ];

    protected string $instanceOf = FilterInterface::class;

    /**
     * Allow many filters of the same type
     */
    protected bool $sharedByDefault = false;

    /**
     * Validate the plugin is of the expected type.
     *
     * Validates against `$instanceOf`.
     */
    public function validate(mixed $instance): void
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                '%s can only create instances of %s; %s is invalid',
                static::class,
                $this->instanceOf,
                is_object($instance) ? $instance::class : gettype($instance)
            ));
        }
    }
}
