# JSON Iterator

Json Iterator is a simple **JSON stream parser for PHP** based on coroutines, developed for extremely large datasets.
Main features are:
- Speed. Performace critical code contains no unnecessary function calls, no regular expressions
and uses native `json_decode` to decode document chunks.
- Ease of use. Just iterate it with `foreach`. No events and callbacks.
- Supports iteration on any subtree of the document, specified by [Json Pointer](https://tools.ietf.org/html/rfc6901)
- Constant memory footprint for unpredictably large JSON documents.

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

foreach (\JsonIterator\JsonIterator::fromFile('big.json') as $name => $data) {
    // 1st iteration: $name === "apple" and $data === ["color" => "red"]
    // 2nd iteration: $name === "pear" and $data === ["color" => "yellow"]
}
```

Parsing an array instead of a dictionary follows the same logic.
The key in a foreach will be a numeric index of an item.

### Parsing JSON document subtree
If you want to iterate only `fruits-key` subtree in this `fruits.json`
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
```php
<?php

foreach (\JsonIterator\JsonIterator::fromFile("fruits.json", "/fruits-key" /* <- Json Pointer */) as $name => $data) {
    // The same as above, which means:
    // 1st iteration: $name === "apple" and $data === ["color" => "red"]
    // 2nd iteration: $name === "pear" and $data === ["color" => "yellow"]
}
```

> Implementation detail:
>
> Value of `fruits` key is not loaded into memory at once, but only one item in
> `fruits` key at a time. It is always one item at a time at the level/subtree
> you are currently iterating. Thus the memory consumption is constant.  
## Parsing API responses

## Error handling
