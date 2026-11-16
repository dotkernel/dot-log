# Grouping log files by date

By default, logs will be written to the same file: `log/dk.log`.

Optionally, you can use date format specifiers wrapped between curly braces in your stream writer's `stream` option to automatically group your logs by day, week, month, year, etc.

Examples:

- `log/dk-{Y}-{m}-{d}.log` will create a new log file each day (eg: `log/dk-2021-01-01.log`)
- `log/dk-{Y}-{W}.log` will create a new log file each week (eg: `log/dk-2021-10.log`)

The full list of format specifiers is available in the [PHP docs for datetime formatting](https://www.php.net/manual/en/datetime.format.php).

The date is resolved when the logger is created, by `Dot\Log\Factory\LoggerFactory`.
A long-running process (a queue worker, for example) keeps writing to the file of the day it started.

## Deleting old files

When `log_lifetime` is set, the stream writer deletes its old files from the same directory.
Each placeholder in the file name matches letters and digits, and the rest of the name must match exactly: with `dk-{Y}-{m}-{d}.log`, the file `dk-2021-01-01.log` matches, `other-2021-01-01.log` does not.

Streams without a placeholder are never cleaned up.
