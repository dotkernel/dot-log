# Formatting Messages

When using `dot-log`, the logged value is not limited to a string.
Arrays and objects can be logged in the context as well.
For better readability, these values are serialized by the formatter.

`dot-log` provides two formatters:

| Name | Class | Output |
| --- | --- | --- |
| `simple` | `Dot\Log\Formatter\Simple` | One line of text. The default for the stream writer |
| `json` | `Dot\Log\Formatter\Json` | One JSON object per line |

The formatter accepts the following parameters:

- `name`: the formatter plugin name, or a class implementing `Dot\Log\Formatter\FormatterInterface`
- `options`: passed to the formatter constructor

The following snippet formats the message as JSON data:

```php
'formatter' => [
    'name' => 'json',
],
```

## Simple formatter options

| Option | Default | Description |
| --- | --- | --- |
| `format` | `%timestamp% %levelName% (%level%): %message% %extra%` | Every `%key%` is replaced with that key of the log event |
| `dateTimeFormat` | `c` (ISO 8601) | The [date format](https://www.php.net/manual/en/datetime.format.php) of the timestamp and of any date value |

The log event has the keys `timestamp`, `level`, `levelName`, `message` and `context`, plus anything added by processors.
The default format does not print the context; add `%context%` to print it as JSON:

```php
'formatter' => [
    'name'    => 'simple',
    'options' => [
        'format'         => '%timestamp% %levelName%: %message% %context%',
        'dateTimeFormat' => 'Y-m-d H:i:s',
    ],
],
```

## How values are formatted

- Dates (`DateTime`, `DateTimeImmutable` or any `DateTimeInterface`) use the configured date format, in both formatters.
- Arrays and iterable objects are written as JSON.
- Objects with a `__toString()` method are cast to string by the `simple` formatter.
- The `json` formatter keeps strings as strings: `"0712345678"` stays a string, it is not turned into a number.
- Invalid UTF-8 is replaced with the Unicode replacement character instead of failing.

To add your own formatter, see [Registering custom plugins](registering-custom-plugins.md).
