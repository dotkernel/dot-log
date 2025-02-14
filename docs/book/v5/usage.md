# Usage

Basic usage of the logger is illustrated below.

The messages are written to see which logs are written and which are not written.

```php
use Dot\Log\Logger;
```

...

```php
$logger = $container->get('dot-log.my_logger');

/** @var Logger $logger */
$logger->emergency('0 EMERG');
$logger->alert('1 ALERT');
$logger->critical('2 CRITICAL');
$logger->error('3 ERR');
$logger->warning('4 WARN');
$logger->notice('5 NOTICE');
$logger->info('6 INF');
$logger->debug('7 debug');
$logger->log(Logger::NOTICE, 'NOTICE from log()');
```

## Placeholders

`dot-log` implements placeholder interpolation as described in PSR-3.
Placeholders MUST be delimited with single opening/closing braces: `{placeholder}`, with the names composed only of the characters `A-Z`, `a-z`, `0-9`, underscore `_`, and period `.`.
Placeholders are interpolated as follows:

* `string` and `numeric` types are replaced directly
* `null` values are not replaced
* Other placeholder values are replaced with the return value of the `gettype` function
