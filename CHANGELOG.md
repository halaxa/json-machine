# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<br>

## master
Nothing yet

<br>

## 1.2.0 - 2024-11-24
### Added
- Recursive iteration via new facade `RecursiveItems`. See **Recursive iteration** in README.

<br>

## 1.1.5 - 2024-11-22
### Added
- Support for PHP 8.4
- Exception on misspelled option name suggests a correct one. 
### Fixed
- Wrong key when combining list and scalar value pointers (#110). Thanks [@daniel-sc](https://github.com/daniel-sc)
### Removed
- Removed support for PHP 7.0, 7.1
<br>

## 1.1.4 - 2023-11-28
- Minor fixes and added some tests.
### Added
- Support for PHP 8.3
- Added PHPStan to build pipeline
### Fixed
- Fixed the case when non-intersecting pointers were considered intersecting (#106). Thanks [@XedinUnknown](https://github.com/XedinUnknown)

<br>

## 1.1.3 - 2022-10-12
### Fixed
- Fix the parsing of nested sub-trees that use wildcards (#83). Thanks [@cerbero90](https://github.com/cerbero90)

<br>

## 1.1.2 - 2022-09-29
### Added
- PHP 8.2 support

### Fixed
- Meaningful error on invalid token. (#86)
- Added missing return type annotation. (#84)

<br>

## 1.1.1 - 2022-03-03
### Fixed
- Fixed warning when generating autoload classmap via composer.

<br>

## 1.1.0 - 2022-02-19
### Added
- Autoloading without Composer. Thanks [@a-sync](https://github.com/a-sync).

<br>

## 1.0.1 - 2022-02-06
### Fixed
- Broken command `make performance-tests`
- Slight performance improvements

<br>

## 1.0.0 - 2022-02-04
### Removed
- Removed deprecated functions `objects()` and `httpClientChunks()`.
- Removed deprecated `JsonMachine` entrypoint class. Use `Items` instead.
- Removed deprecated `Decoder` interface. Use `ItemDecoder` instead.
- Removed `Parser::getJsonPointer()`. Use `Parser::getJsonPointers()`/`Items::getJsonPointers()` instead.
- Removed `Parser::getJsonPointerPath()`. No replacement. Was not useful for anything other than testing and exposed internal implementation.

### Changed
#### Simplified and fixed decoding
- JSON Pointer parts between slashes (a.k.a reference tokens) must be valid encoded JSON strings to be [JSON Pointer RFC 6901](https://tools.ietf.org/html/rfc6901) compliant.
It means that no internal key decoding is performed anymore. You will have to change your JSON Pointers if you match against keys with escape sequences.
```diff
Items::fromString(
    '{"quotes\"": [1, 2, 3]}',
-   ['pointer' => '/quotes"']
+   ['pointer' => '/quotes\"']
);
```
- Method `ItemDecoder::decodeInternalKey()` was deleted as well as related `ValidStringResult`.
They are not used anymore as described in previous point.
- `PassThruDecoder` does not decode keys anymore. Both the key and the value yielded are raw JSON now.

#### Other
- Default decoding structure of `Parser` is object. (You won't notice that unless you use `Parser` class directly)
- `SyntaxError` renamed to `SyntaxErrorException`
- `Items::__construct` accepts the options array instead of separate arguments. (You won't notice that unless you instantiate `Items` class directly)
- `Lexer` renamed to `Tokens`
- `DebugLexer` renamed to `TokensWithDebugging`

### Added
- Multiple JSON Pointers can be specified as an array in `pointer` option. See README. Thanks [@fwolfsjaeger](https://github.com/fwolfsjaeger). 
- New methods available during iteration: `Items::getCurrentJsonPointer()` and `Items::getMatchedJsonPointer()`
to track where you are. See README. Thanks [@fwolfsjaeger](https://github.com/fwolfsjaeger).

### Fixed
- Incorrect position information of `TokensWithDebugging::getPosition()`. Was constantly off by 1-2 bytes.

<br>

## 0.8.0
### Changed
- Internal decoders moved to `ItemDecoder`. `ErrorWrappingDecoder` decorator now requires `ItemDecoder` as well.
- Dropped PHP 5.6 support.

### Deprecated
- `JsonMachine\JsonMachine` entry point class is deprecated, use `JsonMachine\Items` instead.
- `JsonMachine\JsonDecoder\Decoder` interface is deprecated. Use `JsonMachine\JsonDecoder\ItemDecoder` instead. 

### Added
- New entry point class `Items` replaces `JsonMachine`.
- Object as default decoding structure instead of array in `Items`.
- `Items::getIterator()` now returns `Parser`'s iterator directly. Call `Items::getIterator()`
instead of `JsonMachine::getIterator()::getIterator()` to get to `Parser`'s iterator if you need it. Fixes
https://stackoverflow.com/questions/63706550
- `Items` uses `options` in its factory methods instead of growing number of many parameters. See **Options** in README.
- `Items` introduces new `debug` option. See **Options** in README.
- Noticeable performance improvements. What took 10 seconds in `0.7.*` takes **about** 7 seconds in `0.8.0`.

<br>

## 0.7.1
### New features
- PHP 8.1 support
- DEV: Build system switched to composer scripts and Makefile

<br>

## 0.7.0
### New features
- Use a `-` in json pointer as a wildcard for an array index. Example: `/users/-/id`. Thanks [@cerbero90](https://github.com/cerbero90)

<br>

## 0.6.1
### Fixed bugs
- Empty dict at the end of an item was causing Syntax error in the next item. Reason: closing `}` did not set object key expectation to `false`. (#41 via PR #42).

<br>

## 0.6.0
### New features
- **New:** Json pointer can find scalar values in JSON document as well as iterable values. See
[Getting single scalar values](README.md#getting-scalar-values)
- Parser ends when the end of the desired data is reached and does not heat up the atmosphere further.
- Optimizations: about 15% speed gain.


### BC breaks
- A json pointer that matches scalar value does not throw anymore, but the scalar value is yielded in foreach.

<br>

## 0.5.0

### New features
- Introduced `FileChunks` class. Takes care of the proper resource management when iterating via `JsonMachine::fromFile()`.
It is used internally, and you probably won't come across it.
- New `ErrorWrappingDecoder`. Use it when you want to skip malformed JSON items. See [Decoders](README.md#decoders).

### BC breaks
- `StreamBytes` and `StringBytes` renamed to `StreamChunks` and `StringChunks`.
These are internal classes, and you probably won't notice the change
unless you use them directly for some reason.

<br>

## 0.4.1
### New features
- Tracking of parsing progress

<br>

## 0.4.0
### New features
- [Decoders](README.md#decoders)
- PHP 8 support (thanks [@snapshotpl](https://github.com/snapshotpl))
### BC breaks
- `ext-json` is not required in `composer.json` anymore, because custom decoder might not need it.
However **built-in decoders depend on it** so it must be present if you use them.
- All exceptions now extend `JsonMachineException` (thanks [@gabimem](https://github.com/gabimem))
- Throws `UnexpectedEndSyntaxErrorException` on an unexpected end of JSON structure (thanks [@gabimem](https://github.com/gabimem))
- Function `httpClientChunks()` is **deprecated** so that compatibility with Symfony HttpClient
is not on the shoulders of JSON Machine maintainer. The code is simple and everyone can make their own
function and maintain it. The code was moved to [examples](examples/symfonyHttpClient.php).
- Function `objects()` is **deprecated**. The way `objects()` works is that it casts decoded arrays
to objects. It brings some unnecessary overhead and risks on huge datasets.
Alternative is to use `ExtJsonDecoder` which decodes items as objects by default (same as `json_decode`).
```php
<?php

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonMachine;

$jsonMachine = JsonMachine::fromFile('path/to.json', '', new ExtJsonDecoder);
```
Therefore no additional casting is required.
- Invalid json object keys will now throw and won't be ignored anymore.
### Fixed bugs
- Decoding of json object keys checks for errors and does not silently ignore them.
