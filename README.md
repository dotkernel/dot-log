# dot-log

DotKernel log component extending and customizing

## Adding The Config Provider
* Enter config/config.php
* If there is no entry for the config provider below, add it:
`\Dot\Log\ConfigProvider::class`
* Make sure it is added before with the Application-Specific components, eg.: \Frontend\App\ConfigProvider.php,  `\Admin\App\ConfigProvider::class`,  `MyProject\ConfigProvider::class` , etc.
* Open the `Dot\Log\ConfigProvider`
* In the dependencies section you will see an absctract factory (LoggerAbstractServiceFactory::class)
* This class responds to "selectors" instead of class names
  - Instead of requesting the `Zend\Log\Logger::class` from the container, dot-log.my_logger should be requested (or just `my_logger` if using zend-log)

## Configuring the writer(s)
Loggers must have at least one writer.

A writer is an object that inherits from `Zend\Log\Writer\AbstractWriter`. A writer's responsibility is to record log data to a storage backend. (from zend-log's writer documentation)



### Writing to a file (stream)
It is possible separate logs into multiple files using writers and filters. 
For example *warnings.log*, *errors.log*, *all_messages.log*.

The following is the simplest example to write all log messages to `/data/logs/dk.log`
```php
return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                     'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Zend\Log\Logger::ALERT, // this is equal to 1
                        'options' => [
                            'stream' => __DIR__.'/../../data/logs/dk.log',
                        ],
                    ],
                ],
            ]
        ],
    ],
];
```
* The `FileWriter` key is optional, otherwise the writers array would be enumerative instead of associative.
* The writer name key is a developer-provided name for that writer, the writer name key is **mandatory**.

The writer priority key is not affecting the errors that are written, it is a way to organize writers, for example:

1 - FILE
2 - SQL
3 - E-mail
It is the most important to write in the file, the sql or e-mail are more probably fail because the servers can be external and offline, the file is on the same server.

The writer priority key is optional.

To write into a file the key stream must be present in the writer options array. This is required only if writing into streams/files.


## Filtering log messages

As per PSR-3 document.

The log levels are: emergency (0), alert (1), critical (2), error (3), warn (4), notice (5), info (6), debug (7) (in order of priority/importance)

Although the plain Logger in Zend Log is not fully compatible with PSR-3, it provides a way to log all of these message types.


The following example has three file writers using filters:
* First Example: `FileWriter` - All messages are logged in `/data/logs/dk.log`
* Second Example: `OnlyWarningsWriter` - Only warnings are logged in `/data/logs/warnings.log`
* Third Example: `WarningOrHigherWriter` - All important messages (`warnings` or more critical) are logged in `/data/logs/important_messages.log`

```php
<?php

return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Zend\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__.'/../../data/logs/dk.log',
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=', 
                                        'priority' => \Zend\Log\Logger::EMERG,
                                    ]
                                ],
                            ],
                        ],
                    ],
                    // Only warnings
                    'OnlyWarningsWriter' => [
                        'name' => 'stream',
                        'priority' => \Zend\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__.'/../../data/logs/warnings_only.log',
                            'filters' => [
                                'warningOnly' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '==',
                                        'priority' => \Zend\Log\Logger::WARN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Warnings and more important messages
                    'WarningOrHigherWriter' => [
                        'name' => 'stream',
                        'priority' => \Zend\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__.'/../../data/logs/important_messages.log',
                            'filters' => [
                                'importantMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        // note, the smaller the priority, the more important is the message
                                        // 0 - emergency, 1 - alert, 2- error, 3 - warn. .etc
                                        'operator' => '<=',
                                        'priority' => \Zend\Log\Logger::WARN,
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

IMPORTANT NOTE: the operator for more important messages is <=, this is because the number representation is smaller for a more important message type.

The filter added on the first writer is equal to not setting a filter, but it was been added to illustrate how to explicitly allow all messages.

It was added opposite to the others just to demonstrate the other operator is also an option.



More examples on filters: https://zendframework.github.io/zend-log/filters/

## Formatting Messages

When using `dot-log` or `zend-log`, the logged value is not limited to a string. Arrays can be logged as well.

For a better readability, these arrays can be serialized.

Zend Log provides String formatting, XML, JSON and FirePHP formatting.



The formatter accepts following parameters:

name - the formatter class (it must implement Zend\Log\Formatter\FormatterInterface)
options - options to pass to the formatter constructor if required


The following formats the message as JSON data:

'formatter' => [
    'name' => \Zend\Log\Formatter\Json::class,
],


### Example with formatter

* The log is used through dot-log
* The logger name is my_logger
* Writes to file: data/logs/dk.log
* Explicitly allows all the messages to be written
* Formats the messages as JSON

```
<?php


return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name' => 'FileWriter',
                        'priority' => \Zend\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__.'/../../data/logs/dk.log',
                            // explicitly log all messages
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => \Zend\Log\Logger::EMERG,
                                    ],
                                ],
                            ],
                            'formatter' => [
                                'name' => \Zend\Log\Formatter\Json::class,
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
Basic usage of the logger is illustraded below.

The messages are written to see which logs are written and which are not written.
```
use Zend\Log\Logger;
```
...
```
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

Sources:
* https://zendframework.github.io/zend-log/
* https://zendframework.github.io/zend-log/writers/
* https://zendframework.github.io/zend-log/filters/

Extracted from [this article](https://www.dotkernel.com/?p=3454&preview=true)

