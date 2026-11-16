# Upgrading from 4.x to 5.x

Version 5 replaced the Laminas-Log-style API of version 4 with PSR-3.
The configuration format and the `dot-log.<name>` services stay the same, apart from the renamed keys below.

## Changes that can break code

| Change | Symptom | Fix |
| --- | --- | --- |
| `Logger` implements `Psr\Log\LoggerInterface` instead of `Dot\Log\LoggerInterface` | Type errors where `Dot\Log\LoggerInterface` is expected | Type-hint `Psr\Log\LoggerInterface` |
| `emerg()`, `crit()`, `err()` and `warn()` removed | Call to undefined method | Use `emergency()`, `critical()`, `error()` and `warning()` |
| `log(int $priority, mixed $message, iterable $extra)` became `log($level, string\|Stringable $message, iterable $context)` and returns `void` | Chained calls such as `$logger->info('a')->info('b')` fail | Call the methods separately. Pass extra data as `$context` |
| Writer config key `priority` renamed to `level` | The key is ignored | Rename `priority` to `level` |
| Event keys `priority`, `priorityName` and `extra` renamed to `level`, `levelName` and `context` | Custom formatters, filters or processors read missing keys; a custom `Simple` format prints `%priority%` literally | Use the new keys, for example `%timestamp% %levelName% (%level%): %message%` |
| The `priority` filter (`Dot\Log\Filter\Priority`) replaced by `level` (`Dot\Log\Filter\Level`) | Plugin `priority` not found | Use `'name' => 'level'` with the options `level` and `operator` |
| Placeholders such as `{user}` are interpolated from the context | Messages containing braces change | Escape or rename the placeholders |

Version 6 makes further changes, see the [6.x upgrade guide](../v6/upgrading.md).
