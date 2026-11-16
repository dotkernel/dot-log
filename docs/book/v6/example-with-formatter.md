# Example with formatter

- The log is used through `dot-log`
- The logger name is `my_logger`
- It writes to file: `log/dk.log`
- It is configured to explicitly write all messages
- The messages are formatted as JSON

```php
<?php

return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name'    => 'stream',
                        'level'   => \Dot\Log\Logger::ALERT,
                        'options' => [
                            'stream'    => __DIR__ . '/../../log/dk.log',
                            // explicitly log all messages
                            'filters'   => [
                                'allMessages' => [
                                    'name'    => 'level',
                                    'options' => [
                                        'operator' => '>=',
                                        'level'    => \Dot\Log\Logger::EMERG,
                                    ],
                                ],
                            ],
                            'formatter' => [
                                'name' => 'json',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

A call to `$logger->warning('user {id} logged in', ['id' => 7])` writes:

```json
{"timestamp":"2026-11-13T16:15:11+00:00","level":4,"levelName":"warning","message":"user 7 logged in","context":{"id":7}}
```
