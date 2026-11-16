<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Filter\Callback;
use Dot\Log\Filter\FilterInterface;
use Dot\Log\Filter\Level;
use Dot\Log\Filter\Regex;
use Dot\Log\Filter\SuppressFilter;
use Dot\Log\Filter\Validator;

/**
 * @extends AbstractPluginManager<FilterInterface>
 */
class FilterPluginManager extends AbstractPluginManager
{
    /** @var array<string, string> */
    protected array $aliases = [
        'callback'       => Callback::class,
        'level'          => Level::class,
        'regex'          => Regex::class,
        'suppress'       => SuppressFilter::class,
        'suppressfilter' => SuppressFilter::class,
        'validator'      => Validator::class,
    ];

    /** @var class-string<FilterInterface> */
    protected string $instanceOf = FilterInterface::class;
}
