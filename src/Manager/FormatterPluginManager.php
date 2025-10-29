<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Formatter\Simple;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

use function gettype;
use function is_object;
use function sprintf;

/**
 * @template F of FormatterInterface
 * @extends AbstractPluginManager<F>
 */
class FormatterPluginManager extends AbstractPluginManager
{
    /** @var string[]  */
    protected $aliases = [
        'simple' => Simple::class,
    ];

    /** @var string[]|callable[] */
    protected $factories = [
        Simple::class => InvokableFactory::class,
    ];

    /** @var class-string<F> */
    protected $instanceOf = FormatterInterface::class;

    /**
     * Allow many formatters of the same type
     *
     * @var bool
     */
    protected $sharedByDefault = false;

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
