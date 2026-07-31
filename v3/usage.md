# Usage

Basic usage of the logger is illustrated below.

The messages are written to see which logs are written and which are not written.

```php
/** @var \Dot\Log\Logger $logger */
$logger = $container->get('dot-log.my_logger');

$logger->emerg('0 EMERG');
$logger->alert('1 ALERT');
$logger->crit('2 CRITICAL');
$logger->err('3 ERR');
$logger->warn('4 WARN');
$logger->notice('5 NOTICE');
$logger->info('6 INF');
$logger->debug('7 debug');
$logger->log(Logger::NOTICE, 'NOTICE from log()');
```
