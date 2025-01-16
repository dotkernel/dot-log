# dot-log

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-log)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-log/4.1.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-log)](https://github.com/dotkernel/dot-log/blob/4.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-log/actions/workflows/continuous-integration.yml/badge.svg?branch=4.0)](https://github.com/dotkernel/dot-log/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-log/graph/badge.svg?token=JX19KTBRCZ)](https://codecov.io/gh/dotkernel/dot-log)

## Adding The Config Provider

* Enter config/config.php
* If there is no entry for the config provider below, add it:
`\Dot\Log\ConfigProvider::class`
* Make sure it is added before with the Application-Specific components, e.g.: `\Frontend\App\ConfigProvider.php`, `\Admin\App\ConfigProvider::class`, `MyProject\ConfigProvider::class` , etc.
* Open the `Dot\Log\ConfigProvider`
* In the dependencies section you will see an abstract factory (`LoggerAbstractServiceFactory::class`)
* This class responds to "selectors" instead of class names
  Instead of requesting the `Dot\Log\Logger::class` from the container, `dot-log.my_logger` should be requested

## Configuring the writer(s)

Loggers must have at least one writer.

A writer is an object that inherits from `Dot\Log\Writer\AbstractWriter`. A writer's responsibility is to record log data to a storage backend.

### Writing to a file (stream)

It is possible separate logs into multiple files using writers and filters.
For example *warnings.log*, *errors.log*, *all_messages.log*.

The following is the simplest example to write all log messages to `/log/dk.log`

```php
return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                     'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Dot\Log\Manager\Logger::ALERT, // this is equal to 1
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                        ],
                    ],
                ],
            ]
        ],
    ],
];
```

* The `FileWriter` key is optional, otherwise the writers array would be enumerative instead of associative.
* The writer name key is a developer-provided name for that writer, the writer name key is **mandatory**.

The writer priority key is not affecting the errors that are written, it is a way to organize writers.

The writer priority key is optional.

To write into a file the key stream must be present in the writer options array. This is required only if writing into streams/files.

## Grouping log files by date

By default, logs will be written to the same file: `log/dk.log`.
Optionally, you can use date format specifiers wrapped between curly braces in your FileWriter's `stream` option, automatically grouping your logs by day, week, month, year etc.
Examples:

* `log/dk-{Y}-{m}-{d}.log` will write every day to a different file (eg: `log/dk-2021-01-01.log`)
* `log/dk-{Y}-{W}.log` will write every week to a different file (eg: `log/dk-2021-10.log`)

The full list of format specifiers is available [here](https://www.php.net/manual/en/datetime.format.php).

## Filtering log messages

As per PSR-3 document.

The log levels are: emergency (0), alert (1), critical (2), error (3), warn (4), notice (5), info (6), debug (7) (in order of priority/importance)

Although the plain Logger in Dot Log is not fully compatible with PSR-3, it provides a way to log all of these message types.

The following example has three file writers using filters:

* First Example: `FileWriter` - All messages are logged in `/log/dk.log`
* Second Example: `OnlyWarningsWriter` - Only warnings are logged in `/log/warnings.log`
* Third Example: `WarningOrHigherWriter` - All important messages (`warnings` or more critical) are logged in `/log/important_messages.log`

```php
<?php

return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Dot\Log\Manager\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=', 
                                        'priority' => \Dot\Log\Manager\Logger::EMERG,
                                    ]
                                ],
                            ],
                        ],
                    ],
                    // Only warnings
                    'OnlyWarningsWriter' => [
                        'name' => 'stream',
                        'priority' => \Dot\Log\Manager\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/warnings_only.log',
                            'filters' => [
                                'warningOnly' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '==',
                                        'priority' => \Dot\Log\Manager\Logger::WARN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Warnings and more important messages
                    'WarningOrHigherWriter' => [
                        'name' => 'stream',
                        'priority' => \Dot\Log\Manager\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/important_messages.log',
                            'filters' => [
                                'importantMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        // note, the smaller the priority, the more important is the message
                                        // 0 - emergency, 1 - alert, 2- error, 3 - warn. .etc
                                        'operator' => '<=',
                                        'priority' => \Dot\Log\Manager\Logger::WARN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

As in the writer configuration, the developer can optionally use keys for associating the filters with a name.

IMPORTANT NOTE: the operator for more important messages is <=, this is because the number representation is smaller for a more important message type.

The filter added on the first writer is equal to not setting a filter, but it has been added to illustrate how to explicitly allow all messages.

It was added opposite to the others just to demonstrate the other operator is also an option.

## Formatting Messages

When using `dot-log`, the logged value is not limited to a string. Arrays can be logged as well.

For a better readability, these arrays can be serialized.

Dot Log provides String formatting and JSON formatting.

The formatter accepts following parameters:

name - the formatter class (it must implement `Dot\Log\Formatter\FormatterInterface`)
options - options to pass to the formatter constructor if required

The following formats the message as JSON data:

```php
'formatter' => [
    'name' => \Dot\Log\Manager\Formatter\Json::class,
],
```

### Example with formatter

* The log is used through dot-log
* The logger name is my_logger
* Writes to file: log/dk.log
* Explicitly allows all the messages to be written
* Formats the messages as JSON

```php
<?php


return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Dot\Log\Manager\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                            // explicitly log all messages
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => \Dot\Log\Manager\Logger::EMERG,
                                    ],
                                ],
                            ],
                            'formatter' => [
                                'name' => \Dot\Log\Manager\Formatter\Json::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

## Usage

Basic usage of the logger is illustrated below.

The messages are written to see which logs are written and which are not written.

```php
use Dot\Log\Manager\Logger;
```

...

```php
$logger = $container->get('dot-log.my_logger');

/** @var Logger $logger */
$logger->emerg('0 EMERG');
$logger->alert('1 ALERT');
$logger->crit('2 CRITICAL');
$logger->err('3 ERR');
$logger->warn('4 WARN');
$logger->notice('5 NOTICE');
$logger->info('6 INF');
$logger->debug('7 debug');
$logger->log(Logger::NOTICE, 'NOTICE from log()');
```

Extracted from [this article](https://www.dotkernel.com/dotkernel/logging-with-dot-log-in-mezzio-and-dotkernel)
