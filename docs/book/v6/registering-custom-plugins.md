# Registering custom plugins

Writers, filters, formatters and processors are plugins.
`dot-log` builds them with four plugin managers, configured under these keys:

| Plugin type | Config key | Interface |
| --- | --- | --- |
| Writer | `dot_log.writer_manager` | `Dot\Log\Writer\WriterInterface` |
| Filter | `dot_log.filter_manager` | `Dot\Log\Filter\FilterInterface` |
| Formatter | `dot_log.formatter_manager` | `Dot\Log\Formatter\FormatterInterface` |
| Processor | `dot_log.processor_manager` | `Dot\Log\Processor\ProcessorInterface` |

Each key accepts:

- `aliases`: a short name mapped to a class name, or to another alias
- `factories`: a class name (or alias) mapped to a factory class name or a callable

```php
<?php

declare(strict_types=1);

return [
    'dot_log' => [
        'filter_manager'    => [
            'aliases' => [
                'business_hours' => \App\Log\BusinessHoursFilter::class,
            ],
        ],
        'formatter_manager' => [
            'factories' => [
                'app_line' => \App\Log\AppLineFormatterFactory::class,
            ],
        ],
        'loggers'           => [
            'my_logger' => [
                'writers' => [
                    [
                        'name'    => 'stream',
                        'options' => [
                            'stream'    => __DIR__ . '/../../log/dk.log',
                            'filters'   => [['name' => 'business_hours']],
                            'formatter' => ['name' => 'app_line'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

## How a plugin name is resolved

1. Aliases are followed, first by exact name, then lower-cased.
1. If a factory is registered for the result, the factory creates the plugin.
1. Otherwise, if the result is a class name, the class is instantiated. The options are passed to the constructor, or nothing when no options were given.
1. Otherwise, if the application container has a service with that name, the service is used.
1. Otherwise, `Dot\Log\Exception\ServiceNotFoundException` is thrown.

The plugin must implement the interface of its type, or `Dot\Log\Exception\InvalidArgumentException` is thrown.
Plugins are never shared: every writer gets its own filter and formatter instances.

## Factories

A factory is called with the application container, the resolved name and the options:

```php
<?php

declare(strict_types=1);

namespace App\Log;

use Dot\Log\Formatter\Simple;
use Psr\Container\ContainerInterface;

class AppLineFormatterFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): Simple
    {
        return new Simple('%timestamp% [%levelName%] %message% %context%');
    }
}
```

This signature is compatible with Laminas Service Manager factories, so existing factories can be reused.

## Using the plugin managers as services

The managers do not need to be container services.
If you want them as services anyway, `Dot\Log\Factory\FilterPluginManagerFactory`, `FormatterPluginManagerFactory`, `ProcessorPluginManagerFactory` and `WriterPluginManagerFactory` create them from the same config keys.
