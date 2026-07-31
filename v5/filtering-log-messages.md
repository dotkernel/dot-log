# Filtering log messages

The following conforms to the `PSR-3: Logger Interface` document.

The log levels are in order of level/importance:

* emergency (0)
* alert (1)
* critical (2)
* error (3)
* warning (4)
* notice (5)
* info (6)
* debug (7)

Because the plain Logger in `dot-log` is fully compatible with PSR-3, it provides a way to log all of these message types.

The following example has three file writers using filters:

* First Example: `FileWriter` - All messages are logged in `/log/dk.log`
* Second Example: `OnlyWarningsWriter` - Only warnings are logged in `/log/warnings.log`
* Third Example: `WarningOrHigherWriter` - All important messages (`warnings` or critical) are logged in `/log/important_messages.log`

```php
<?php

return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name'    => 'FileWriter',
                        'level'   => \Dot\Log\Logger::ALERT,
                        'options' => [
                            'stream'  => __DIR__ . '/../../log/dk.log',
                            'filters' => [
                                'allMessages' => [
                                    'name'    => 'level',
                                    'options' => [
                                        'operator' => '>=', 
                                        'level'    => \Dot\Log\Logger::EMERG,
                                    ]
                                ],
                            ],
                        ],
                    ],
                    // Only warnings
                    'OnlyWarningsWriter' => [
                        'name'    => 'stream',
                        'level'   => \Dot\Log\Logger::ALERT,
                        'options' => [
                            'stream'  => __DIR__ . '/../../log/warnings_only.log',
                            'filters' => [
                                'warningOnly' => [
                                    'name'    => 'level',
                                    'options' => [
                                        'operator' => '==',
                                        'level'    => \Dot\Log\Logger::WARN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Warnings and more important messages
                    'WarningOrHigherWriter' => [
                        'name'    => 'stream',
                        'level'   => \Dot\Log\Logger::ALERT,
                        'options' => [
                            'stream'  => __DIR__ . '/../../log/important_messages.log',
                            'filters' => [
                                'importantMessages' => [
                                    'name'    => 'level',
                                    'options' => [
                                        // note, the smaller the level, the more important the message
                                        // 0 - emergency, 1 - alert, 2- error, 3 - warn, etc.
                                        'operator' => '<=',
                                        'level'    => \Dot\Log\Logger::WARN,
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

> The operator for more important messages is `<=`, this is because the number representation is smaller for a more important message type.

The filter added on the first writer is equivalent to not setting a filter, but it was added to illustrate the usage of the operator to explicitly allow all messages.
