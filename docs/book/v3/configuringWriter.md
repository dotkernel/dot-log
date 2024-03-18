## Configuring the writer(s)
Loggers must have at least one writer.

A writer is an object that inherits from `Laminas\Log\Writer\AbstractWriter`. A writer's responsibility is to record log data to a storage backend. (from laminas-log's writer documentation)

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
                        'priority' => \Laminas\Log\Logger::ALERT, // this is equal to 1
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


The writer priority key is not affecting the errors that are written, it is a way to organize writers, for example:

1 - FILE
2 - SQL
3 - E-mail
It is the most important to write in the file, the sql or e-mail are more probably fail because the servers can be external and offline, the file is on the same server.

The writer priority key is optional.

To write into a file the key stream must be present in the writer options array. This is required only if writing into streams/files.