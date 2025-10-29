<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Factory\WriterFactory;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\Stream;
use Dot\Log\Writer\WriterInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;

use function gettype;
use function is_object;
use function sprintf;

/**
 * @template W of WriterInterface
 * @extends AbstractPluginManager<W>
 */
class WriterPluginManager extends AbstractPluginManager
{
    /** @var string[] */
    protected $aliases = [
        'noop'   => Noop::class,
        'stream' => Stream::class,

        // The following are for backwards compatibility only; users
        // should update their code to use the noop writer instead.
        'null'                 => Noop::class,
        'laminaslogwriternull' => Noop::class,
    ];

    /** @var string[]|callable[] */
    protected $factories = [
        Noop::class   => WriterFactory::class,
        Stream::class => WriterFactory::class,
    ];

    /** @var class-string<W> */
    protected $instanceOf = WriterInterface::class;

    /**
     * Allow many writers of the same type
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
