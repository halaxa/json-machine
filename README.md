# JSON Machine

Json Machine is a Fast, efficient and easy-to-use JSON stream parser based on coroutines
developed for unpredictably long JSON streams or documents. Main features are:

- Ease of use. Just iterate JSON of any size with `foreach`. No events and callbacks.
- Constant memory footprint for unpredictably large JSON documents.
- Speed. Performace critical code contains no unnecessary function calls, no regular expressions
and uses native `json_decode` to decode JSON document chunks.
- Supports efficient iteration on any subtree of the document, specified by [Json Pointer](https://tools.ietf.org/html/rfc6901)

## Examples
### Parsing simple JSON document
Let's say that `big.json` contains this really big JSON document:
```json
// big.json
{
    "apple": {
        "color": "red"
    },
    "pear": {
        "color": "yellow"
    }
}
``` 
It can be parsed this way:
```php
<?php

$jsonStream = \JsonMachine\JsonMachine::fromFile('big.json');

foreach ($jsonStream as $name => $data) {
    // 1st iteration: $name === "apple" and $data === ["color" => "red"]
    // 2nd iteration: $name === "pear" and $data === ["color" => "yellow"]
}
```

Parsing an array instead of a dictionary follows the same logic.
The key in a foreach will be a numeric index of an item.

### Parsing JSON document subtree
If you want to iterate only `fruits-key` subtree in this `fruits.json`:
```json
// fruits.json
{
    "fruits-key": {
        "apple": {
            "color": "red"
        },
        "pear": {
            "color": "yellow"
        }
    }
}
```
do it like this:
```php
<?php

$jsonStream = \JsonMachine\JsonMachine::fromFile("fruits.json", "/fruits-key" /* <- Json Pointer */);
foreach ($jsonStream as $name => $data) {
    // The same as above, which means:
    // 1st iteration: $name === "apple" and $data === ["color" => "red"]
    // 2nd iteration: $name === "pear" and $data === ["color" => "yellow"]
}
```

> Note:
>
> Value of `fruits-key` is not loaded into memory at once, but only one item in
> `fruits-key` at a time. It is always one item in memory at a time at the level/subtree
> you are currently iterating. Thus the memory consumption is constant.  
## Parsing API responses
If you use this library to parse large API responses, all you need to do is passing the stream resource
of your api response to `JsonMachine::fromStream($streamResource)`.

### GuzzleHttp example
Guzzle uses its own streams, but they can be converted back to PHP streams by calling
`\GuzzleHttp\Psr7\StreamWrapper::getResource()`. See [GuzzleHttp example](src/examples/guzzleHttp.php)

## Error handling
When any part of the JSON stream is malformed, `SyntaxError` exception is thrown. Better solution is on the way.

## Running tests
```bash
composer install
vendor/bin/phpunit
```
To run tests on all supported PHP platforms install docker to your machine and run `tests/docker-run-all-platforms.sh`
