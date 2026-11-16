# Configuring the writer(s)

Loggers must have at least one writer.

A writer is an object that implements `Dot\Log\Writer\WriterInterface`, usually by extending `Dot\Log\Writer\AbstractWriter`.
A writer's responsibility is to record log data to a storage backend.

## Writing to a file (stream)

You can separate logs into multiple files using writers and filters.
For example, *warnings.log*, *errors.log*, *all_messages.log*.

The following is the simplest example to write all log messages to `/log/dk.log`:

```php
return [
    'dot_log' => [
        'loggers' => [
            'my_logger' => [
                'writers' => [
                    'FileWriter' => [
                        'name'    => 'stream',
                        'level'   => \Dot\Log\Logger::ALERT, // this is equal to 1
                        'options' => [
                            'stream'       => __DIR__ . '/../../log/dk.log',
                            'log_lifetime' => null, // OPTIONAL
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

- The `FileWriter` key is a free-form name for readability. It is optional: the writers array can also be a list.
- The `name` key is **mandatory** and selects the writer plugin: `stream`, `noop`, a class name, or a name registered under `dot_log.writer_manager` (see [Registering custom plugins](registering-custom-plugins.md)).
- The `level` key is optional and defaults to `1`. It does not filter messages: it orders the writers of a logger, and writers with a higher value write first. The key `priority` is accepted as an alias.
- The `options` key is passed to the writer.

## Stream writer options

| Option | Required | Description |
| --- | --- | --- |
| `stream` | yes | A file path, a stream URL such as `php://stderr`, or an open stream resource |
| `mode` | no | The `fopen()` mode, default `a`. Must be `a` for an open resource |
| `log_separator` | no | Written after each line, default `PHP_EOL` |
| `chmod` | no | Permissions of a new log file, default `0666` |
| `formatter` | no | The formatter, default `simple`. See [Formatting messages](formatting-messages.md) |
| `filters` | no | The filters. See [Filtering log messages](filtering-log-messages.md) |
| `log_lifetime` | no | Number of days after which the writer's log files are deleted. See below |

## Automatic Log File Deletion

The `options.log_lifetime` key specifies a number of days after which the specific `writer`'s log files will be **deleted even if not empty**.

The key can be omitted completely or set to `null` in order to skip this feature.

The deletion runs when the logger is destroyed, and only for streams named with date placeholders (see [Grouping log files by date](grouping-log-files-by-date.md)).
Only files in the stream's directory whose name matches the stream's name pattern are deleted.

> This feature will delete all relevant log files for the configured `writer`, take care not to misconfigure it in a production environment!
