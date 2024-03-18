## Filtering log messages

As per PSR-3 document.

The log levels are: emergency (0), alert (1), critical (2), error (3), warn (4), notice (5), info (6), debug (7) (in order of priority/importance)

Although the plain Logger in Laminas Log is not fully compatible with PSR-3, it provides a way to log all of these message types.


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
                        'priority' => \Laminas\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=', 
                                        'priority' => \Laminas\Log\Logger::EMERG,
                                    ]
                                ],
                            ],
                        ],
                    ],
                    // Only warnings
                    'OnlyWarningsWriter' => [
                        'name' => 'stream',
                        'priority' => \Laminas\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/warnings_only.log',
                            'filters' => [
                                'warningOnly' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '==',
                                        'priority' => \Laminas\Log\Logger::WARN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Warnings and more important messages
                    'WarningOrHigherWriter' => [
                        'name' => 'stream',
                        'priority' => \Laminas\Log\Logger::ALERT,
                        'options' => [
                            'stream' => __DIR__ . '/../../log/important_messages.log',
                            'filters' => [
                                'importantMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        // note, the smaller the priority, the more important is the message
                                        // 0 - emergency, 1 - alert, 2- error, 3 - warn. .etc
                                        'operator' => '<=',
                                        'priority' => \Laminas\Log\Logger::WARN,
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

The filter added on the first writer is equal to not setting a filter, but it was been added to illustrate how to explicitly allow all messages.

It was added opposite to the others just to demonstrate the other operator is also an option.



More examples on filters: https://docs.laminas.dev/laminas-log/filters/