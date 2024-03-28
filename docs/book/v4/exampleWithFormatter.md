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

## Usage
Basic usage of the logger is illustraded below.

The messages are written to see which logs are written and which are not written.
```php
use Laminas\Log\Logger;
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