# Usage

Basic usage of the logger is illustrated below.

The messages are written to see which logs are written and which are not written.

```php
/** @var \Psr\Log\LoggerInterface $logger */
$logger = $container->get('dot-log.my_logger');

$logger->emergency('0 EMERG');
$logger->alert('1 ALERT');
$logger->critical('2 CRITICAL');
$logger->error('3 ERR');
$logger->warning('4 WARN');
$logger->notice('5 NOTICE');
$logger->info('6 INF');
$logger->debug('7 debug');
$logger->log(\Dot\Log\Logger::NOTICE, 'NOTICE from log()');
$logger->log(\Psr\Log\LogLevel::NOTICE, 'NOTICE from log() with a PSR-3 level name');
```

`log()` accepts a level code from `0` to `7` or a PSR-3 level name, and throws `Dot\Log\Exception\InvalidArgumentException` for anything else.

## Placeholders

`dot-log` implements placeholder interpolation as described in PSR-3.
Placeholders MUST be delimited with single opening/closing braces: `{placeholder}`, with the names composed only of the characters `A-Z`, `a-z`, `0-9`, underscore `_`, and period `.`.
Placeholders are interpolated as follows:

| Context value | Replacement |
| --- | --- |
| string, integer, float | The value |
| boolean | `true` or `false` |
| object with `__toString()` | The string value |
| `DateTimeInterface` | The date in RFC 3339 format, for example `2026-01-02T03:04:05+00:00` |
| other object | `[object ClassName]` |
| array and other types | `[array]`, and so on |
| `null` or a missing key | Not replaced |

```php
$logger->info('User {user} logged in at {time}', [
    'user' => $user,              // a Stringable object
    'time' => new DateTimeImmutable(),
]);
```

## Logging PHP errors and exceptions

The logger can register itself as PHP error handler, exception handler and fatal-error shutdown function, either with options or with static methods:

```php
'dot_log' => [
    'loggers' => [
        'my_logger' => [
            'errorhandler'                 => true,
            'exceptionhandler'             => true,
            'fatal_error_shutdownfunction' => true,
            'writers'                      => [
                // ...
            ],
        ],
    ],
],
```

```php
\Dot\Log\Logger::registerErrorHandler($logger);
\Dot\Log\Logger::registerExceptionHandler($logger);
\Dot\Log\Logger::registerFatalErrorShutdownFunction($logger);
```

- Errors are logged only when they are included in `error_reporting()`.
- The context of a logged error has the keys `errno` (the PHP error number, for example `E_WARNING`), `file` and `line`.
- An exception is logged with every previous exception, the innermost first. The context has `file`, `line` and `trace`.

## Processors

Processors change the event before it reaches the writers:

| Name | Effect |
| --- | --- |
| `backtrace` | Adds `file`, `line`, `class` and `function` of the code that called the logger to the context. Option `ignoredNamespaces` adds namespaces to skip, besides `Dot\Log` and `Psr\Log` |
| `psrplaceholder` | Interpolates placeholders. The logger already does this, so it is only needed for events built by hand |
| `requestid` | Adds a `requestId` to the context, the same for every event of a request |
| `referenceid` | Adds a `referenceId` to the context. It can be set with `setReferenceId()` |

```php
'my_logger' => [
    'processors' => [
        ['name' => 'backtrace'],
        ['name' => 'requestid'],
    ],
    'writers'    => [
        // ...
    ],
],
```
