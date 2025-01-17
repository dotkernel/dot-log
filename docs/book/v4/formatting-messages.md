# Formatting Messages

When using `dot-log`, the logged value is not limited to a string. Arrays can be logged as well. For better readability, these arrays can be serialized. Dot Log provides String and JSON formatting.

The formatter accepts following parameters:

* name - the formatter class (it must implement `Dot\Log\Formatter\FormatterInterface`)
* options - passed to the formatter constructor if required

The following snippet formats the message as JSON data:

```php
'formatter' => [
    'name' => \Dot\Log\Formatter\Json::class,
],
```
