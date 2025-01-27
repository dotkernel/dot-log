# Grouping log files by date

By default, logs will be written to the same file: `log/dk.log`.

Optionally, you can use date format specifiers wrapped between curly braces in your FileWriter's `stream` option to automatically group your logs by day, week, month, year etc.

Examples:

* `log/dk-{Y}-{m}-{d}.log` will create a new log file each day (eg: log/dk-2021-01-01.log)
* `log/dk-{Y}-{W}.log` will create a new log file each week (eg: log/dk-2021-10.log)

The full list of format specifiers is available in the [PHP docs for datetime formatting](https://www.php.net/manual/en/datetime.format.php).
