# Changelog

## master
### Removed
- Removed deprecated functions `objects()` and `httpClientChunks()`.
- Removed deprecated `JsonMachine` entrypoint class.
- Removed deprecated `Decoder` interface.

### Changed
- Default decoding structure of `Parser` is object. (You won't notice that unless you use `Parser` class directly)
- `Items::__construct` accepts the options array instead of separate arguments. (You won't notice that unless you instantiate `Items` class directly)

### Deprecated
- `JsonMachine\Parser::getJsonPointer()`
- `JsonMachine\Parser::getJsonPointerPath()`

### Added
- Multiple JSON Pointers can be specified as an array in `pointer` option. See README. Thanks @fwolfsjaeger. 
- New methods available during iteration: `Items::getCurrentJsonPointer()` and `Items::getMatchedJsonPointer()`
to track where you are. See README. Thanks @fwolfsjaeger.

<br>
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
<br>

## 0.7.1
### New features
- PHP 8.1 support
- DEV: Build system switched to composer scripts and Makefile

<br>
<br>

## 0.7.0
### New features
- Use a `-` in json pointer as a wildcard for an array index. Example: `/users/-/id`. Thanks @cerbero90

<br>
<br>

## 0.6.1
### Fixed bugs
- Empty dict at the end of an item was causing Syntax error in the next item. Reason: closing `}` did not set object key expectation to `false`. (#41 via PR #42).

<br>
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
<br>

## 0.4.1
### New features
- Tracking of parsing progress

<br>
<br>

## 0.4.0
### New features
- [Decoders](README.md#decoders)
- PHP 8 support (thanks @snapshotpl)
### BC breaks
- `ext-json` is not required in `composer.json` anymore, because custom decoder might not need it.
However **built-in decoders depend on it** so it must be present if you use them.
- All exceptions now extend `JsonMachineException` (thanks @gabimem)
- Throws `UnexpectedEndSyntaxErrorException` on an unexpected end of JSON structure (thanks @gabimem)
- Function `httpClientChunks()` is **deprecated** so that compatibility with Symfony HttpClient
is not on the shoulders of JSON Machine maintainer. The code is simple and everyone can make their own
function and maintain it. The code was moved to [examples](examples/symfonyHttpClient.php).
- Function `objects()` is **deprecated**. The way `objects()` works is that it casts decoded arrays
to objects. It brings some unnecessary overhead and risks on huge datasets.
Alternative is to use `ExtJsonDecoder` which decodes items as objects by default (same as `json_decode`).
```php
<?php

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\Items;

$jsonMachine = Items::fromFile('path/to.json', '', new ExtJsonDecoder);
```
Therefore no additional casting is required.
- Invalid json object keys will now throw and won't be ignored anymore.
### Fixed bugs
- Decoding of json object keys checks for errors and does not silently ignore them.
