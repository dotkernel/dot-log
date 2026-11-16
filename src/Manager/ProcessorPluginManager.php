<?php

declare(strict_types=1);

namespace Dot\Log\Manager;

use Dot\Log\Processor\Backtrace;
use Dot\Log\Processor\ProcessorInterface;
use Dot\Log\Processor\PsrPlaceholder;
use Dot\Log\Processor\ReferenceId;
use Dot\Log\Processor\RequestId;

/**
 * @extends AbstractPluginManager<ProcessorInterface>
 */
class ProcessorPluginManager extends AbstractPluginManager
{
    /** @var array<string, string> */
    protected array $aliases = [
        'backtrace'      => Backtrace::class,
        'psrplaceholder' => PsrPlaceholder::class,
        'referenceid'    => ReferenceId::class,
        'requestid'      => RequestId::class,
    ];

    /** @var class-string<ProcessorInterface> */
    protected string $instanceOf = ProcessorInterface::class;
}
