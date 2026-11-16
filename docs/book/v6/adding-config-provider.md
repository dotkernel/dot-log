# Registering Loggers

Each logger is a container service named `dot-log.<logger name>`, created by `Dot\Log\Factory\LoggerFactory` from the configuration under `dot_log.loggers.<logger name>`.

Register one factory entry per logger, for example in `config/autoload/logger.global.php`:

```php
<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            'dot-log.my_logger' => \Dot\Log\Factory\LoggerFactory::class,
        ],
    ],
    'dot_log'      => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name'    => 'stream',
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

Then request the logger by that name:

```php
/** @var \Psr\Log\LoggerInterface $logger */
$logger = $container->get('dot-log.my_logger');
```

Follow the [Configuring the writer(s)](configuring-writer.md) chapter for the writer options.

## Supported containers

The factories are plain classes with `__invoke(ContainerInterface $container, string $requestedName)`.
They work with any PSR-11 container that accepts a factory per service and passes the requested service name to it as the second argument, for example Laminas Service Manager.

`dot-log` does not require `laminas/laminas-servicemanager`.
Applications that use it as their container require it themselves.

## The config provider

`Dot\Log\ConfigProvider` is kept so that applications listing it in `config/config.php` keep working, but it registers nothing.
You can remove it from `config/config.php`.

> Version 5 registered an abstract factory that answered every `dot-log.*` name.
> Version 6 needs an explicit entry for each logger: see [Upgrading](upgrading.md).

## The default logger service

`Dot\Log\LoggerServiceFactory` creates a logger from the `log` config key, for applications that want to type-hint a single default logger:

```php
<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            \Dot\Log\Logger::class => \Dot\Log\LoggerServiceFactory::class,
        ],
    ],
    'log'          => [
        'writers' => [
            ['name' => 'stream', 'options' => ['stream' => __DIR__ . '/../../log/app.log']],
        ],
    ],
];
```
