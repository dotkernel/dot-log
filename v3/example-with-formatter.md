# Example with formatter

* The log is used through `dot-log`
* The logger name is `my_logger`
* It writes to file: `log/dk.log`
* It is configured to explicitly write all messages  
* The messages are formatted as JSON

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
                            // explicitly log all messages
                            'filters' => [
                                'allMessages' => [
                                    'name' => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => \Laminas\Log\Logger::EMERG,
                                    ],
                                ],
                            ],
                            'formatter' => [
                                'name' => \Laminas\Log\Formatter\Json::class,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```
