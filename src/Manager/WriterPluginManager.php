<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Factory\WriterFactory;
use Dot\Log\Writer\Noop;
use Dot\Log\Writer\Stream;
use Dot\Log\Writer\WriterInterface;
use Psr\Container\ContainerInterface;

/**
 * @extends AbstractPluginManager<WriterInterface>
 */
class WriterPluginManager extends AbstractPluginManager
{
    /** @var array<string, string> */
    protected array $aliases = [
        'noop'   => Noop::class,
        'stream' => Stream::class,

        // The following are for backwards compatibility only; users
        // should update their code to use the noop writer instead.
        'null'                 => Noop::class,
        'laminaslogwriternull' => Noop::class,
    ];

    /** @var array<string, callable|string> */
    protected array $factories = [
        Noop::class   => WriterFactory::class,
        Stream::class => WriterFactory::class,
    ];

    /** @var class-string<WriterInterface> */
    protected string $instanceOf = WriterInterface::class;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        ?ContainerInterface $container = null,
        array $config = [],
        protected ?FilterPluginManager $filterPluginManager = null,
        protected ?FormatterPluginManager $formatterPluginManager = null,
    ) {
        parent::__construct($container, $config);
    }

    /**
     * Writers built by this manager share its filter and formatter managers, unless the options name others.
     *
     * @param array<array-key, mixed>|null $options
     */
    public function build(string $name, ?array $options = null): WriterInterface
    {
        if ($this->filterPluginManager !== null && ! isset($options['filter_manager'])) {
            $options                   = (array) $options;
            $options['filter_manager'] = $this->filterPluginManager;
        }

        if ($this->formatterPluginManager !== null && ! isset($options['formatter_manager'])) {
            $options                      = (array) $options;
            $options['formatter_manager'] = $this->formatterPluginManager;
        }

        return parent::build($name, $options);
    }
}
