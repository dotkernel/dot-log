# Configuring the writer(s)

Loggers must have at least one writer.

A writer is an object that inherits from `Dot\Log\Writer\AbstractWriter`.
A writer's responsibility is to record log data to a storage backend.

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
                        'name'    => 'FileWriter',
                        'level'   => \Dot\Log\Logger::ALERT, // this is equal to 1
                        'options' => [
                            'stream' => __DIR__ . '/../../log/dk.log',
                            'log_lifetime' => null, // OPTIONAL
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

The `level` key does not affect the errors that are written.
It is a way to organize writers.

The `level` key is optional.

The key `stream` is required only if writing into streams/files.

The `options.log_lifetime` key is optional.

## Automatic Log File Deletion

The `options.log_lifetime` key specifies a date after which the specific `writer`'s log files will be **deleted even if not empty**.

The key can be omitted completely or set to `null` in order to skip this feature.

To make use of automatic log file deletion, set the value to the number of **days** after which a log file should be deleted.

> This feature will delete all relevant log files for the configured `writer`, take care not to misconfigure it in a production environment!
