# Upgrading from 5.x to 6.x

Version 6 removes the dependency on `laminas/laminas-servicemanager`, supports PHP 8.3 to 8.6, and fixes several bugs.
Some fixes change what is written to the logs.

## Requirements

- PHP 8.3, 8.4, 8.5 or 8.6. PHP 8.2 is no longer supported.
- Any PSR-11 container that passes the requested service name to factories, for example Laminas Service Manager.
- `laminas/laminas-servicemanager` and `laminas/laminas-validator` are no longer installed by `dot-log`, not even for its own tests. Require them in your application if you use them.

## Replacing the abstract factory

Version 5 registered `Dot\Log\Factory\LoggerAbstractServiceFactory`, which answered every `dot-log.<name>` service.
Version 6 removes it: register `Dot\Log\Factory\LoggerFactory` for each logger you use.

Before, `config/config.php` listed `\Dot\Log\ConfigProvider::class`, and `config/autoload/logger.global.php` held only the logger configuration:

```php
return [
    'dot_log' => [
        'loggers' => [
            'default_logger' => [/* ... */],
        ],
    ],
];
```

After, `config/autoload/logger.global.php` also registers the logger:

```php
return [
    'dependencies' => [
        'factories' => [
            'dot-log.default_logger' => \Dot\Log\Factory\LoggerFactory::class,
        ],
    ],
    'dot_log'      => [
        'loggers' => [
            'default_logger' => [/* ... */],
        ],
    ],
];
```

`Dot\Log\ConfigProvider` still exists but registers nothing, so it can stay in `config/config.php` or be removed.

Packages that fetch a logger by name, such as `dotkernel/dot-errorhandler` with `'logger' => 'dot-log.default_logger'`, keep working once that name is registered.

## Changes that can break code

| Change | Symptom | Fix |
| --- | --- | --- |
| `LoggerAbstractServiceFactory` removed | `ServiceNotFoundException` for `dot-log.<name>` | Register `'dot-log.<name>' => LoggerFactory::class` (see above) |
| `ConfigProvider` returns `[]` | Services `LogWriterManager`, `LogFilterManager`, `LogFormatterManager`, `LogProcessorManager`, `Dot\Log\Logger` and the plugin manager class names are not registered | You rarely need them: `LoggerFactory` builds the managers from `dot_log.*_manager`. If you do, register the `*PluginManagerFactory` and `LoggerServiceFactory` classes yourself |
| The plugin managers no longer extend `Laminas\ServiceManager\AbstractPluginManager` | Calls to `setService()`, `setFactory()`, `setAlias()`, `configure()` with other keys, or `get()` with options fail | Configure plugins under `dot_log.*_manager` with `aliases` and `factories`, see [Registering custom plugins](registering-custom-plugins.md). Use `build($name, $options)` |
| `Laminas\ServiceManager\Exception\InvalidServiceException` and `ServiceNotFoundException` are no longer thrown by the managers | `catch` blocks miss the exception | Catch `Dot\Log\Exception\InvalidArgumentException`, or `Dot\Log\Exception\ServiceNotFoundException` for unknown plugins (it implements `Psr\Container\NotFoundExceptionInterface`) |
| `Dot\Log\Formatter\Base` renamed to `AbstractFormatter`, now abstract, and its `format()` replaced by `normalizeEvent()` | Fatal error: class `Base` not found | Extend `AbstractFormatter`, implement `format(array $event): string`, and call `normalizeEvent()` for the normalized values |
| `FormatterInterface::format()` takes `array` and returns `string` | Fatal error in custom formatters declared with `iterable` or returning arrays | Change the signature to `format(array $event): string` |
| `FilterInterface::filter()` returns `bool` | Fatal error in custom filters declared as `bool\|int\|null` | Return `bool` |
| `Logger::getWriterPluginManager()`, `getProcessorPluginManager()`, `writerPlugin()`, `processorPlugin()` no longer return `null` | Subclasses overriding them with nullable return types fail | Remove the `?` from the return types |
| Event `timestamp` is a `DateTimeImmutable` | Custom formatters or processors checking `instanceof DateTime` miss it | Check `instanceof DateTimeInterface` |
| Context placeholders: `Stringable` objects are cast, booleans become `true`/`false`, other objects become `[object ClassName]`, arrays become `[array]` | Messages read `user ID-42` instead of `user object` | Nothing to do, unless something parses the old output |
| `Json` formatter no longer uses `JSON_NUMERIC_CHECK` | `"0712345678"` is written as a string, not as `712345678` | Update log parsers that relied on numbers |
| Error handler: `context.errno` is the PHP error number | `errno` was the log level (for example `4`), it is now `E_WARNING` (`2`) or similar | Update log parsers that read `errno` |
| `Backtrace` processor ignores `Dot\Log` and `Psr\Log` frames | `file`, `line`, `class` and `function` now show your code instead of `Dot\Log\Logger::log` | Nothing to do. Ignored namespaces now match whole namespace segments |
| `Regex` filter validates its pattern first | `InvalidArgumentException` instead of `TypeError` for a missing pattern | Catch `Dot\Log\Exception\InvalidArgumentException` |
| `Level` filter validates its operator | `InvalidArgumentException` at configuration time instead of `ValueError` at log time | Use one of the documented operators |
| `Stream` log clean-up only matches the writer's own file names | Files that matched the loose old pattern are no longer deleted | Nothing to do |
| `Logger::log()` rejects non-integer levels such as `2.5` | `InvalidArgumentException` | Pass a level code or a PSR-3 level name |
| `laminas/laminas-validator` is no longer installed | Class `Laminas\Validator\...` not found in your validator configuration | `composer require laminas/laminas-validator`. The `validator` filter itself works without it: it accepts any object with an `isValid()` method |
| `Validator::$validator` is typed `object` instead of `Laminas\Validator\ValidatorInterface` | Subclasses redeclaring the property with the old type fail | Declare it as `object`, or do not redeclare it |

## Fixed behaviour you may have worked around

- Custom plugins configured under `dot_log.writer_manager`, `filter_manager`, `formatter_manager` and `processor_manager` were ignored. They are now used.
- The writer `level` (or `priority`) was documented as optional but omitting it threw a `TypeError`. It now defaults to `1`.
- A `Stream` writer without a formatter crashed. It now uses the `simple` formatter.
- Formatter options were ignored when the formatter was given by class name. They are now passed to the constructor.
- The `simple` formatter wrote a literal `%extra%` for events from the logger. It now writes nothing for an empty `extra`.
- Destroying a logger emptied the writers queue returned by `getWriters()`.

## New

- `Dot\Log\Factory\LoggerFactory`.
- The `json` formatter alias and the `callback` filter.
- `priority` as an alias of the writer and processor `level` key.
- `Logger::createErrorHandler()` and `Logger::createExceptionHandler()` return the handlers without registering them.
- `Stream::getOldLogsPattern()` shows which file names the log clean-up matches.
