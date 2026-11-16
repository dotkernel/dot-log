<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Formatter\Json;
use Dot\Log\Formatter\Simple;

/**
 * @extends AbstractPluginManager<FormatterInterface>
 */
class FormatterPluginManager extends AbstractPluginManager
{
    /** @var array<string, string> */
    protected array $aliases = [
        'json'   => Json::class,
        'simple' => Simple::class,
    ];

    /** @var class-string<FormatterInterface> */
    protected string $instanceOf = FormatterInterface::class;
}
