# Changelog

All notable changes to `dotkernel/dot-log` are documented in this file.

## 6.0.0 - TBD

### Changed

* PHPUnit 12 and PHPStan level 8, with no ignored errors
* The plugin managers no longer extend `Laminas\ServiceManager\AbstractPluginManager`; they extend `Dot\Log\Manager\AbstractPluginManager` and work with any PSR-11 container
* `Dot\Log\ConfigProvider` returns an empty array: register `Dot\Log\Factory\LoggerFactory` for each `dot-log.<name>` logger
* The factories are plain classes with `Psr\Container` imports only
* `laminas/laminas-validator` is no longer required, not even for development; it is listed under `suggest`. `laminas/laminas-stdlib` and `psr/container` are declared
* The `Validator` filter accepts any object with an `isValid()` method instead of only `Laminas\Validator\ValidatorInterface`
* `FormatterInterface::format()` takes `array` and returns `string`; `FilterInterface::filter()` returns `bool`
* `Dot\Log\Formatter\Base` renamed to `AbstractFormatter`; `normalizeEvent()` replaces its array-returning `format()`
* The event `timestamp` is a `DateTimeImmutable`
* Placeholders: `Stringable` values are cast to string, dates use RFC 3339, booleans become `true`/`false`, other values become `[object Class]` or `[type]`
* The `Json` formatter no longer converts numeric strings to numbers (`JSON_NUMERIC_CHECK` removed) and substitutes invalid UTF-8
* The `Level` filter compares integers and validates its operator; the `Regex` filter validates its pattern before use
* Plugin managers throw `Dot\Log\Exception\InvalidArgumentException` and `Dot\Log\Exception\ServiceNotFoundException`

### Added

* PHP 8.6 support
* `Dot\Log\Factory\LoggerFactory`
* `json` formatter alias and `callback` filter
* `priority` as an alias of the writer and processor `level` key
* `Logger::createErrorHandler()`, `Logger::createExceptionHandler()` and `Logger::placeholderValue()`
* `Stream::getOldLogsPattern()`
* `provide: psr/log-implementation`
* Upgrade guides for 4.x to 5.x and 5.x to 6.x, and a page on registering custom plugins

### Deprecated

* Nothing

### Removed

* PHP 8.2 support
* The `laminas/laminas-servicemanager` requirement, including the development requirement
* `Dot\Log\Factory\LoggerAbstractServiceFactory`
* `Dot\Log\Formatter\Base`

### Fixed

* Custom plugins configured under `dot_log.*_manager` were ignored
* Omitting the documented-as-optional writer or processor `level` threw a `TypeError`
* A `Stream` writer without a formatter crashed on the first write
* Formatter options were ignored when the formatter was given by class name
* The `simple` formatter wrote a literal `%extra%` for events from the logger
* The error handler logged the log level as `errno` instead of the PHP error number
* The `Backtrace` processor reported `Dot\Log\Logger::log` instead of the caller
* `DateTimeImmutable` values were not formatted
* The `Regex` filter and `AbstractWriter::write()` could leave an error handler active
* Logger options given as a generator were ignored
* Destroying a logger emptied the queue returned by `getWriters()`
* The log clean-up pattern could match, and delete, unrelated files in the log directory
* The documented writer configuration (`'name' => 'FileWriter'`) and `log.global.php.dist` did not work

## 5.3.1 - 2026-07-31

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* File permission issues (#94)

## 4.1.4 - 2026-07-31

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* File permission issues (#93)

## 4.0.6 - 2026-07-31

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* File permission issues (#92)

## 5.3.0 - 2025-10-29

### Changed

* Nothing

### Added

* PHP 8.4 and 8.5 support (#84)

### Deprecated

* Nothing

### Removed

* PHP 8.1 support (#84)

### Fixed

* Nothing

## 4.1.3 - 2025-10-29

### Changed

* Nothing

### Added

* PHP 8.4 and 8.5 support (#83)

### Deprecated

* Nothing

### Removed

* PHP 8.1 support (#83)

### Fixed

* Nothing

## 4.0.5 - 2025-10-29

### Changed

* Nothing

### Added

* PHP 8.4 and 8.5 support (#82)

### Deprecated

* Nothing

### Removed

* PHP 8.1 support (#82)

### Fixed

* Nothing

## 5.2.0 - 2025-10-14

### Changed

* Nothing

### Added

* Automatic deletion of log files with the `log_lifetime` writer option (#80)

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 5.1.0 - 2025-02-24

### Changed

* Replaced Psalm with PHPStan (#75)
* Replaced `Dot\Log\Exception\InvalidArgumentException` (#77)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 5.0.0 - 2025-02-14

### Changed

* Implemented PSR-3 (#68)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 4.1.2 - 2025-02-14

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Wrong link in README.md (#70)
* Wrong text in the documentation (#71)
* Updated SECURITY.md (#72)

## 3.5.4 - 2025-02-14

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Wrong link in README.md (#70)
* Updated SECURITY.md (#72)

## 4.1.1 - 2025-01-28

### Changed

* Psalm 6 (#65)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 4.1.0 - 2025-01-17

### Changed

* Nothing

### Added

* `laminas/laminas-servicemanager` 4 support (#44)

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 4.0.4 - 2024-09-13

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* `docs/book/index.md` symlink (#60)

## 4.0.3 - 2024-09-13

### Changed

* Nothing

### Added

* Unit tests

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 4.0.2 - 2024-08-30

### Changed

* Updated README.md

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Accessing resources before initialization

## 4.0.1 - 2024-08-29

### Changed

* Updated `log.global.php.dist`

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 4.0.0 - 2024-08-29

### Changed

* Updated README.md

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* The `laminas/laminas-log` dependency

### Fixed

* Nothing

## 3.5.3 - 2024-06-30

### Changed

* Marked the package as a wrapper in the documentation

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.5.2 - 2024-06-28

### Changed

* Marked the package as a wrapper

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.5.1 - 2024-05-22

### Changed

* Updated the documentation

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.5.0 - 2024-05-03

### Changed

* Updated README.md

### Added

* Documentation (#40)

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.4.7 - 2024-02-28

### Changed

* laminas/laminas-servicemanager dependency update from 3.10.0 to 3.22

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

## 3.4.6 - 2024-02-28

### Changed

* laminas/laminas-log dependency update from 2.16 to 2.17 (introduced PHP 8.3 support)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

## 3.4.5 - 2024-02-27

### Changed

* dot-mail dependency update from 4.0 to 4.1 (introduced PHP 8.3 support)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

## 3.4.4 - 2023-12-06

### Changed

* Updated README.md

### Added

* PHP 8.3 support

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.4.3 - 2023-08-29

### Changed

* Nothing

### Added

* Codecov workflow and badge

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.4.2 - 2023-08-11

### Changed

* Renamed `static-analisys.yml` to `static-analysis.yml`

### Added

* Static analysis badge (#24)

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.4.1 - 2023-07-25

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Compatibility with newer `laminas/laminas-log` versions

## 3.4.0 - 2023-07-19

### Changed

* Code quality improvements (#20)

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.3.0 - 2023-03-29

### Changed

* Nothing

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* `laminas/laminas-dependency-plugin`

### Fixed

* Nothing

## 3.2.1 - 2022-10-27

### Changed

* Set `laminas/laminas-log` to the fixed version 2.15.2

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.2.0 - 2022-03-22

### Changed

* Updated the PHP version constraint to allow 8.0 and 8.1

### Added

* PHP 8.0 and 8.1 compatibility

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.1.1 - 2021-04-26

### Changed

* Nothing

### Added

* Date-based variables in the stream writer's `stream` option

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 3.1.0 - 2020-11-03

### Changed

* Composer 2 compatibility.
* Update composer.json to require version 2 of laminas/laminas-dependency-plugin to be Composer 2 compatible.

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* bump min version of laminas/laminas-dependency-plugin

## 2.0.0 - 2020-01-30

### Changed

* Laminas and Mezzio migration.

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Support

### Fixed

* Nothing

## 1.1.2 - 2018-11-13

### Changed

* nothing

### Added

* usage documentation

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 1.1.1 - 2018-05-04

### Changed

* dependencies updates
* using `dotkernel/dot-mail:^1.0`

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 1.1.0 - 2017-03-16

### Changed

* updates to PSR11 container where possible
* dependencies updates

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 1.0.1 - 2017-03-11

Updated php file headers doc blocks

### Added

* Nothing

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing

## 1.0.0 - 2017-03-09

Initial tagged release

### Added

* Everything

### Deprecated

* Nothing

### Removed

* Nothing

### Fixed

* Nothing
