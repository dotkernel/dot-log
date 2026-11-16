<?php

declare(strict_types=1);

namespace Dot\Log;

/**
 * Kept so that applications listing it keep working. dot-log registers no services: register a
 * Dot\Log\Factory\LoggerFactory entry for each "dot-log.<name>" logger the application uses.
 */
class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [];
    }
}
