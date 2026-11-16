# dot-log

Robust, composite PSR-3 compliant logger with filtering and formatting, usable with any PSR-11 container.

## Version History

| Branch | Container            | Log style implementation | PHP Version                                                                                             |
|--------|----------------------|--------------------------|---------------------------------------------------------------------------------------------------------|
| 6.0    | Any PSR-11 container | PSR-Log                  | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/6.0.0) |
| 5.0    | Service Manager 4    | PSR-Log                  | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/5.3.1) |
| 4.1    | Service Manager 4    | Laminas Log style        | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/4.1.4) |
| 4.0    | Service Manager 3    | Laminas Log style        | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/4.0.6) |
| 3.0    | Service Manager 3    | Laminas Log              | ![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/3.5.4) |

## Documentation

Documentation is available at: https://docs.dotkernel.org/dot-log/.

Upgrading from 5.x? Read the [6.x upgrade guide](https://docs.dotkernel.org/dot-log/v6/upgrading/).

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-log)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/6.0.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/blob/6.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-log/actions/workflows/continuous-integration.yml/badge.svg?branch=6.0)](https://github.com/dotkernel/dot-log/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-log/graph/badge.svg?token=JX19KTBRCZ)](https://codecov.io/gh/dotkernel/dot-log)
[![PHPStan](https://github.com/dotkernel/dot-log/actions/workflows/static-analysis.yml/badge.svg?branch=6.0)](https://github.com/dotkernel/dot-log/actions/workflows/static-analysis.yml)

## Installation

```shell
composer require dotkernel/dot-log
```

## Quick start

Register a factory for each logger, and configure it under `dot_log.loggers`, for example in `config/autoload/logger.global.php`:

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
                        // the writer plugin: "stream", "noop" or a class name
                        'name'    => 'stream',
                        'options' => [
                            'stream'    => __DIR__ . '/../../log/dk-{Y}-{m}-{d}.log',
                            'formatter' => ['name' => 'json'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

Then fetch the logger from the container and use it as any PSR-3 logger:

```php
/** @var \Psr\Log\LoggerInterface $logger */
$logger = $container->get('dot-log.my_logger');

$logger->warning('User {id} failed to log in', ['id' => 7]);
```

The factories work with any PSR-11 container that passes the requested service name to the factory, for example Laminas Service Manager.
The `dot-log` package does not require `laminas/laminas-servicemanager`.

See the documentation for writers, filters, formatters, processors and custom plugins.
