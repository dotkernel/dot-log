## Grouping log files by date
By default, logs will be written to the same file: `log/dk.log`.
Optionally, you can use date format specifiers wrapped between curly braces in your FileWriter's `stream` option, automatically grouping your logs by day, week, month, year etc.
Examples:
* `log/dk-{Y}-{m}-{d}.log` will write every day to a different file (eg: log/dk-2021-01-01.log)
* `log/dk-{Y}-{W}.log` will write every week to a different file (eg: log/dk-2021-10.log)

The full list of format specifiers is available [here](https://www.php.net/manual/en/datetime.format.php).