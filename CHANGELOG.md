# master

## New features
- Dropped support for PHP 5.6
## BC breaks
## Fixed bugs

# 0.4.0
## New features
- [Custom decoder](README.md#custom-decoder)
- PHP 8 support (thanks @snapshotpl)
## BC breaks
- `ext-json` is not required in `composer.json` anymore, because custom decoder might not need it.
However **built-in decoders depend on it** so it must be present if you use them.
- All exceptions now extend `JsonMachineException` (thanks @gabimem)
- Throws `UnexpectedEndSyntaxErrorException` on an unexpected end of JSON structure (thanks @gabimem)
- Function `httpClientChunks()` is **deprecated** so that compatibility with Symfony HttpClient
is not on the shoulders of JSON Machine maintainer. The code is simple and everyone can make their own
function and maintain it. The code was moved to [examples](src/examples/symfonyHttpClient.php).
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
## Fixed bugs
- Decoding of json object keys checks for errors and does not silently ignore them.
