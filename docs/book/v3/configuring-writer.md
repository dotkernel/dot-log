# Configuring the writer(s)

Loggers must have at least one writer.

A writer is an object that inherits from `Laminas\Log\Writer\AbstractWriter`.
A writer's responsibility is to record log data to a storage backend. (from laminas-log's writer documentation).

## Writing to a file (stream)

You can separate logs into multiple files using writers and filters.
For example *warnings.log*, *errors.log*, *all_messages.log*.

The following is the simplest example to write all log messages to `/log/dk.log`:

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
* The `name` key is a developer-provided name for that writer, the writer name key is **mandatory**.

The `priority` key does not affect the errors that are written.
It is a way to organize writers, for example:

* 1 - FILE
* 2 - SQL
* 3 - E-mail

The most important things to write in the file, the sql or e-mail are usually fails because the servers can be external and offline, but the file is on the same server.

The `priority` key is optional.

The key `stream` is required only if writing into streams/files.
