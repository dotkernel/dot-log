<?php

declare(strict_types=1);

namespace Dot\Log\Writer;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Filter\FilterInterface;
use Dot\Log\Filter\Level;
use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Manager\FilterPluginManager;
use Dot\Log\Manager\FormatterPluginManager;
use Laminas\Stdlib\ErrorHandler;
use Psr\Container\ContainerExceptionInterface;
use Traversable;

use function class_exists;
use function get_debug_type;
use function is_array;
use function is_int;
use function is_string;
use function iterator_to_array;
use function sprintf;

use const E_WARNING;

abstract class AbstractWriter implements WriterInterface
{
    protected ?FilterPluginManager $filterPlugins = null;

    protected ?FormatterPluginManager $formatterPlugins = null;

    /** @var list<FilterInterface> */
    protected array $filters = [];

    protected ?FormatterInterface $formatter = null;

    /**
     * Use Laminas\Stdlib\ErrorHandler to report errors during calls to write
     */
    protected bool $convertWriteErrorsToExceptions = true;

    /**
     * Error level passed to Laminas\Stdlib\ErrorHandler::start for errors reported during calls to write
     */
    protected int $errorsToExceptionsConversionLevel = E_WARNING;

    /**
     * Set options for a writer. Accepted options are:
     * - filter_manager: FilterPluginManager instance or class name
     * - formatter_manager: FormatterPluginManager instance or class name
     * - filters: a filter, or a list of filters (FilterInterface, level as int, plugin name, or [name, options])
     * - formatter: a formatter (FormatterInterface, plugin name, or [name, options])
     *
     * @param iterable<string, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function __construct(?iterable $options = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (! is_array($options)) {
            return;
        }

        $filterManager = $options['filter_manager'] ?? null;
        if (is_string($filterManager) || $filterManager instanceof FilterPluginManager) {
            $this->setFilterPluginManager($filterManager);
        } elseif ($filterManager !== null) {
            throw $this->invalidManagerOption('filter_manager', $filterManager);
        }

        $formatterManager = $options['formatter_manager'] ?? null;
        if (is_string($formatterManager) || $formatterManager instanceof FormatterPluginManager) {
            $this->setFormatterPluginManager($formatterManager);
        } elseif ($formatterManager !== null) {
            throw $this->invalidManagerOption('formatter_manager', $formatterManager);
        }

        if (isset($options['filters'])) {
            $filters = $options['filters'];
            if (is_int($filters) || is_string($filters) || $filters instanceof FilterInterface) {
                $this->addFilter($filters);
            } elseif (is_array($filters)) {
                foreach ($filters as $filter) {
                    if (is_int($filter) || is_string($filter) || $filter instanceof FilterInterface) {
                        $this->addFilter($filter);
                    } elseif (is_array($filter)) {
                        if (! isset($filter['name']) || ! is_string($filter['name'])) {
                            throw new InvalidArgumentException('Options must contain a name for the filter');
                        }
                        $this->addFilter($filter['name'], $this->optionsOrNull($filter['options'] ?? null));
                    }
                }
            }
        }

        if (isset($options['formatter'])) {
            $formatter = $options['formatter'];
            if (is_string($formatter) || $formatter instanceof FormatterInterface) {
                $this->setFormatter($formatter);
            } elseif (is_array($formatter)) {
                if (! isset($formatter['name']) || ! is_string($formatter['name'])) {
                    throw new InvalidArgumentException('Options must contain a name for the formatter');
                }
                $this->setFormatter($formatter['name'], $this->optionsOrNull($formatter['options'] ?? null));
            }
        }
    }

    /**
     * Add a filter specific to this writer.
     *
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function addFilter(int|string|FilterInterface $filter, ?array $options = null): WriterInterface
    {
        if (is_int($filter)) {
            $filter = new Level($filter);
        }

        if (is_string($filter)) {
            $filter = $this->filterPlugin($filter, $options);
        }

        $this->filters[] = $filter;
        return $this;
    }

    public function getFilterPluginManager(): FilterPluginManager
    {
        if (null === $this->filterPlugins) {
            $this->filterPlugins = new FilterPluginManager();
        }
        return $this->filterPlugins;
    }

    /**
     * @param string|FilterPluginManager $plugins a FilterPluginManager instance or class name
     */
    public function setFilterPluginManager(string|FilterPluginManager $plugins): static
    {
        if (is_string($plugins) && class_exists($plugins)) {
            $plugins = new $plugins();
        }
        if (! $plugins instanceof FilterPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'Filter plugin manager must extend %s; received %s',
                FilterPluginManager::class,
                get_debug_type($plugins)
            ));
        }

        $this->filterPlugins = $plugins;
        return $this;
    }

    /**
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function filterPlugin(string $name, ?array $options = null): FilterInterface
    {
        return $this->getFilterPluginManager()->build($name, $options);
    }

    public function getFormatterPluginManager(): FormatterPluginManager
    {
        if (null === $this->formatterPlugins) {
            $this->formatterPlugins = new FormatterPluginManager();
        }
        return $this->formatterPlugins;
    }

    /**
     * @param string|FormatterPluginManager $plugins a FormatterPluginManager instance or class name
     */
    public function setFormatterPluginManager(string|FormatterPluginManager $plugins): static
    {
        if (is_string($plugins) && class_exists($plugins)) {
            $plugins = new $plugins();
        }
        if (! $plugins instanceof FormatterPluginManager) {
            throw new InvalidArgumentException(sprintf(
                'Formatter plugin manager must extend %s; received %s',
                FormatterPluginManager::class,
                get_debug_type($plugins)
            ));
        }

        $this->formatterPlugins = $plugins;
        return $this;
    }

    /**
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function formatterPlugin(string $name, ?array $options = null): FormatterInterface
    {
        return $this->getFormatterPluginManager()->build($name, $options);
    }

    /**
     * Log a message to this writer.
     *
     * @param array<string, mixed> $event
     */
    public function write(array $event): void
    {
        foreach ($this->filters as $filter) {
            if (! $filter->filter($event)) {
                return;
            }
        }

        $errorHandlerStarted = false;
        if ($this->convertWriteErrorsToExceptions && ! ErrorHandler::started()) {
            ErrorHandler::start($this->errorsToExceptionsConversionLevel);
            $errorHandlerStarted = true;
        }

        $error = null;
        try {
            $this->doWrite($event);
        } finally {
            if ($errorHandlerStarted) {
                $error = ErrorHandler::stop();
            }
        }

        if ($error !== null) {
            throw new RuntimeException('Unable to write', 0, $error);
        }
    }

    /**
     * @param array<array-key, mixed>|null $options
     * @throws ContainerExceptionInterface
     */
    public function setFormatter(FormatterInterface|string $formatter, ?array $options = null): WriterInterface
    {
        if (is_string($formatter)) {
            $formatter = $this->formatterPlugin($formatter, $options);
        }

        $this->formatter = $formatter;
        return $this;
    }

    protected function getFormatter(): ?FormatterInterface
    {
        return $this->formatter;
    }

    protected function hasFormatter(): bool
    {
        return $this->formatter instanceof FormatterInterface;
    }

    public function setConvertWriteErrorsToExceptions(bool $convertErrors): void
    {
        $this->convertWriteErrorsToExceptions = $convertErrors;
    }

    /**
     * Perform shutdown activities such as closing open resources
     */
    public function shutdown(): void
    {
    }

    /**
     * Write a message to the log
     *
     * @param array<string, mixed> $event
     */
    abstract protected function doWrite(array $event): void;

    /**
     * @return array<array-key, mixed>|null
     */
    private function optionsOrNull(mixed $options): ?array
    {
        if ($options instanceof Traversable) {
            return iterator_to_array($options);
        }

        return is_array($options) ? $options : null;
    }

    private function invalidManagerOption(string $key, mixed $value): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'Option "%s" must be a plugin manager instance or class name; received %s',
            $key,
            get_debug_type($value)
        ));
    }
}
