<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Processor\Backtrace;
use Dot\Log\Processor\ProcessorInterface;
use Dot\Log\Processor\PsrPlaceholder;
use Dot\Log\Processor\ReferenceId;
use Dot\Log\Processor\RequestId;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

use function gettype;
use function is_object;
use function sprintf;

/**
 * @template P of ProcessorPluginManager
 * @extends AbstractPluginManager<P>
 */
class ProcessorPluginManager extends AbstractPluginManager
{
    /** @var string[] */
    protected array $aliases = [
        'backtrace'      => Backtrace::class,
        'psrplaceholder' => PsrPlaceholder::class,
        'referenceid'    => ReferenceId::class,
        'requestid'      => RequestId::class,
    ];

    protected array $factories = [
        Backtrace::class      => InvokableFactory::class,
        PsrPlaceholder::class => InvokableFactory::class,
        ReferenceId::class    => InvokableFactory::class,
        RequestId::class      => InvokableFactory::class,
    ];

    protected string $instanceOf = ProcessorInterface::class;

    /**
     * Allow many processors of the same type
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
