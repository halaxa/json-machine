# <img align="right" src="img/github.png" alt="JSON Machine" />

Very easy to use and memory efficient drop-in replacement for inefficient iteration of big JSON files or streams
for PHP >=7.2. See [TL;DR](#tl-dr). No dependencies in production except optional `ext-json`. README in sync with the code

[![Build Status](https://github.com/halaxa/json-machine/actions/workflows/makefile.yml/badge.svg)](https://github.com/halaxa/json-machine/actions)
[![codecov](https://img.shields.io/codecov/c/gh/halaxa/json-machine?label=phpunit%20%40covers)](https://codecov.io/gh/halaxa/json-machine)
[![Latest Stable Version](https://img.shields.io/github/v/release/halaxa/json-machine?color=blueviolet&include_prereleases&logoColor=white)](https://packagist.org/packages/halaxa/json-machine)
[![Monthly Downloads](https://img.shields.io/packagist/dt/halaxa/json-machine?color=%23f28d1a)](https://packagist.org/packages/halaxa/json-machine)

---
NEW in version `1.2.0` - [Recursive iteration](#recursive)

---

* [TL;DR](#tl-dr)
* [Introduction](#introduction)
* [Parsing JSON documents](#parsing-json-documents)
  + [Parsing a document](#simple-document)
  + [Parsing a subtree](#parsing-a-subtree)
  + [Parsing nested values in arrays](#parsing-nested-values)
  + [Parsing a single scalar value](#getting-scalar-values)
  + [Parsing multiple subtrees](#parsing-multiple-subtrees)
  + [Recursive iteration](#recursive)
  + [What is JSON Pointer anyway?](#json-pointer)
* [Options](#options)
* [Parsing streaming responses from a JSON API](#parsing-json-stream-api-responses)
  + [GuzzleHttp](#guzzlehttp)
  + [Symfony HttpClient](#symfony-httpclient)
* [Tracking the progress](#tracking-parsing-progress)
* [Decoders](#decoders)
  + [Available decoders](#available-decoders)
* [Error handling](#error-handling)
  + [Catching malformed items](#malformed-items)
* [Parser efficiency](#on-parser-efficiency)
  + [Streams / files](#streams-files)
  + [In-memory JSON strings](#in-memory-json-strings)
* [Troubleshooting](#troubleshooting)
  + ["I'm still getting Allowed memory size ... exhausted"](#step1)
  + ["That didn't help"](#step2)
  + ["I am still out of luck"](#step3)
* [Installation](#installation)
* [Development](#development)
  + [Non containerized](#non-containerized)
  + [Containerized](#containerized)
* [Support](#support)
* [License](#license)

---

<a name="tl-dr"></a>
## TL;DR
```diff
<?php

use \JsonMachine\Items;

// this often causes Allowed Memory Size Exhausted,
// because it loads all the items in the JSON into memory
- $users = json_decode(file_get_contents('500MB-users.json'));

// this has very small memory footprint no matter the file size
// because it loads items into memory one by one
+ $users = Items::fromFile('500MB-users.json');

foreach ($users as $id => $user) {
    // just process $user as usual
    var_dump($user->name);
}
```

Random access like `$users[42]` is not yet possible.
Use above-mentioned `foreach` and find the item or use [JSON Pointer](#parsing-a-subtree).

Counting the items is possible via [`iterator_count($users)`](https://www.php.net/manual/en/function.iterator-count.php).
Remember it will still have to internally iterate the whole thing to get the count and thus will take about the same time
as iterating it and counting in a loop.

Requires `ext-json` if used out of the box but doesn't if a custom decoder is used. See [Decoders](#decoders).

Follow [CHANGELOG](CHANGELOG.md).

<a name="introduction"></a>
## Introduction
JSON Machine is an efficient, easy-to-use and fast JSON stream/pull/incremental/lazy (whatever you name it) parser
based on generators developed for unpredictably long JSON streams or documents. Main features are:

- Constant memory footprint for unpredictably large JSON documents.
- Ease of use. Just iterate JSON of any size with `foreach`. No events and callbacks.
- Efficient iteration on any subtree of the document, specified by [JSON Pointer](#json-pointer)
- Speed. Performance critical code contains no unnecessary function calls, no regular expressions
and uses native `json_decode` to decode JSON document items by default. See [Decoders](#decoders).
- Parses not only streams but any iterable that produces JSON chunks.
- Thoroughly tested. More than 200 tests and 1000 assertions.

<a name="parsing-json-documents"></a>
## Parsing JSON documents

<a name="simple-document"></a>
### Parsing a document
Let's say that `fruits.json` contains this huge JSON document:
```json
// fruits.json
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

use \JsonMachine\Items;

$fruits = Items::fromFile('fruits.json');

foreach ($fruits as $name => $data) {
    // 1st iteration: $name === "apple" and $data->color === "red"
    // 2nd iteration: $name === "pear" and $data->color === "yellow"
}
```

Parsing a json array instead of a json object follows the same logic.
The key in a foreach will be a numeric index of an item.

If you prefer JSON Machine to return arrays instead of objects, use `new ExtJsonDecoder(true)` as a decoder.
```php
<?php

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\Items;

$objects = Items::fromFile('path/to.json', ['decoder' => new ExtJsonDecoder(true)]);
```


<a name="parsing-a-subtree"></a>
### Parsing a subtree
If you want to iterate only `results` subtree in this `fruits.json`:
```json
// fruits.json
{
    "results": {
        "apple": {
            "color": "red"
        },
        "pear": {
            "color": "yellow"
        }
    }
}
```
use JSON Pointer `/results` as `pointer` option:
```php
<?php

use \JsonMachine\Items;

$fruits = Items::fromFile('fruits.json', ['pointer' => '/results']);
foreach ($fruits as $name => $data) {
    // The same as above, which means:
    // 1st iteration: $name === "apple" and $data->color === "red"
    // 2nd iteration: $name === "pear" and $data->color === "yellow"
}
```

> Note:
>
> Value of `results` is not loaded into memory at once, but only one item in
> `results` at a time. It is always one item in memory at a time at the level/subtree
> you are currently iterating. Thus, the memory consumption is constant.

<a name="parsing-nested-values"></a>
### Parsing nested values in arrays
The JSON Pointer spec also allows to use a hyphen (`-`) instead of a specific array index. JSON Machine interprets
it as a wildcard which matches any **array index** (not any object key). This enables you to iterate nested values in
arrays without loading the whole item.

Example:
```json
// fruitsArray.json
{
    "results": [
        {
            "name": "apple",
            "color": "red"
        },
        {
            "name": "pear",
            "color": "yellow"
        }
    ]
}
```

To iterate over all colors of the fruits, use the JSON Pointer `"/results/-/color"`.

```php
<?php

use \JsonMachine\Items;

$fruits = Items::fromFile('fruitsArray.json', ['pointer' => '/results/-/color']);

foreach ($fruits as $key => $value) {
    // 1st iteration:
    $key == 'color';
    $value == 'red';
    $fruits->getMatchedJsonPointer() == '/results/-/color';
    $fruits->getCurrentJsonPointer() == '/results/0/color';

    // 2nd iteration:
    $key == 'color';
    $value == 'yellow';
    $fruits->getMatchedJsonPointer() == '/results/-/color';
    $fruits->getCurrentJsonPointer() == '/results/1/color';
}
```

<a name="getting-scalar-values"></a>
### Parsing a single scalar value
You can parse a single scalar value anywhere in the document the same way as a collection. Consider this example:
```json
// fruits.json
{
    "lastModified": "2012-12-12",
    "apple": {
        "color": "red"
    },
    "pear": {
        "color": "yellow"
    },
    // ... gigabytes follow ...
}
``` 
Get the scalar value of `lastModified` key like this:
```php
<?php

use \JsonMachine\Items;

$fruits = Items::fromFile('fruits.json', ['pointer' => '/lastModified']);
foreach ($fruits as $key => $value) {
    // 1st and final iteration:
    // $key === 'lastModified'
    // $value === '2012-12-12'
}
```
When parser finds the value and yields it to you, it stops parsing. So when a single scalar value is in the beginning
of a gigabytes-sized file or stream, it just gets the value from the beginning in no time and with almost no memory
consumed.

The obvious shortcut is:
```php
<?php

use \JsonMachine\Items;

$fruits = Items::fromFile('fruits.json', ['pointer' => '/lastModified']);
$lastModified = iterator_to_array($fruits)['lastModified'];
```
Single scalar value access supports array indices in JSON Pointer as well.

<a name="parsing-multiple-subtrees"></a>
### Parsing multiple subtrees

It is also possible to parse multiple subtrees using multiple JSON Pointers. Consider this example:
```json
// fruits.json
{
    "lastModified": "2012-12-12",
    "berries": [
        {
          "name": "strawberry", // not a berry, but whatever ...
          "color": "red"
        },
        {
          "name": "raspberry", // the same ...
          "color": "red"
        }
    ],
    "citruses": [
      {
          "name": "orange",
          "color": "orange"
      },
      {
          "name": "lime",
          "color": "green"
      }
    ]
}
``` 
To iterate over all berries and citrus fruits, use the JSON pointers `["/berries", "/citrus"]`. The order of pointers
does not matter. The items will be iterated in the order of appearance in the document.
```php
<?php

use \JsonMachine\Items;

$fruits = Items::fromFile('fruits.json', [
    'pointer' => ['/berries', '/citruses']
]);

foreach ($fruits as $key => $value) {
    // 1st iteration:
    $value == ["name" => "strawberry", "color" => "red"];
    $fruits->getCurrentJsonPointer() == '/berries';

    // 2nd iteration:
    $value == ["name" => "raspberry", "color" => "red"];
    $fruits->getCurrentJsonPointer() == '/berries';

    // 3rd iteration:
    $value == ["name" => "orange", "color" => "orange"];
    $fruits->getCurrentJsonPointer() == '/citruses';

    // 4th iteration:
    $value == ["name" => "lime", "color" => "green"];
    $fruits->getCurrentJsonPointer() == '/citruses';
}
```

<a name="recursive"></a>
### Recursive iteration
Use `RecursiveItems` instead of `Items` when the JSON structure is difficult or even impossible to handle with `Items`
and JSON pointers or the individual items you iterate are too big to handle.
On the other hand it's notably slower than `Items`, so bear that in mind.

When `RecursiveItems` encounters a list or dict in the JSON, it returns a new instance of itself
which can then be iterated over and the cycle repeats.
Thus, it never returns a PHP array or object, but only either scalar values or `RecursiveItems`.
No JSON dict nor list will ever be fully loaded into memory at once.

Let's see an example with many, many users with many, many friends:
```json
// users.json
[
  {
    "username": "user",
    "e-mail": "user@example.com",
    "friends": [
      {
        "username": "friend1",
        "e-mail": "friend1@example.com"
      },
      {
        "username": "friend2",
        "e-mail": "friend2@example.com"
      }
    ]
  }
]
```

```php
<?php

use JsonMachine\RecursiveItems

$users = RecursiveItems::fromFile('users.json');
foreach ($users as $user) {
    /** @var $user RecursiveItems */
    foreach ($user as $field => $value) {
        if ($field === 'friends') {
            /** @var $value RecursiveItems */
            foreach ($value as $friend) {
                /** @var $friend RecursiveItems */
                foreach ($friend as $friendField => $friendValue) {
                    $friendField == 'username';
                    $friendValue == 'friend1';
                }
            }
        }
    }
}
```

> If you break an iteration of such lazy deeper-level (i.e. you skip some `"friends"` via `break`)
> and advance to a next value (i.e. next `user`), you will not be able to iterate it later.
> JSON Machine must iterate it in the background to be able to read next value.
> Such an attempt will result in closed generator exception.

#### Convenience methods of `RecursiveItems`
- `toArray(): array`
If you are sure that a certain instance of RecursiveItems is pointing to a memory-manageable data structure
(for example, $friend), you can call `$friend->toArray()`, and the item will materialize into a plain PHP array.

- `advanceToKey(int|string $key): scalar|RecursiveItems`
When searching for a specific key in a collection (for example, `'friends'` in `$user`),
you do not need to use a loop and a condition to search for it.
Instead, you can simply call `$user->advanceToKey("friends")`.
It will iterate for you and return the value at this key. Calls can be chained.
It also supports **array like syntax** for advancing to and getting following indices.
So `$user['friends']` would be an alias for `$user->advanceToKey('friends')`. Calls can be chained.
Keep in mind that it's just an alias - **you won't be able to random-access previous indices**
after using this directly on `RecursiveItems`. It's just a syntax sugar.
Use `toArray()` if you need random access to indices on a record/item.

The previous example could thus be simplified as follows:
```php
<?php

use JsonMachine\RecursiveItems

$users = RecursiveItems::fromFile('users.json');
foreach ($users as $user) {
    /** @var $user RecursiveItems */
    foreach ($user['friends'] as $friend) { // or $user->advanceToKey('friends')
        /** @var $friend RecursiveItems */
        $friendArray = $friend->toArray();
        $friendArray['username'] === 'friend1';
    }
}
```
Chaining allows you to do something like this:
```php
<?php

use JsonMachine\RecursiveItems

$users = RecursiveItems::fromFile('users.json');
$users[0]['friends'][1]['username'] === 'friend2';

```

#### Also `RecursiveItems implements \RecursiveIterator`
So you can use for example PHP's builtin tools to work over `\RecursiveIterator` like those:

- [RecursiveCallbackFilterIterator](https://www.php.net/manual/en/class.recursivecallbackfilteriterator.php) 
- [RecursiveFilterIterator](https://www.php.net/manual/en/class.recursivefilteriterator.php) 
- [RecursiveRegexIterator](https://www.php.net/manual/en/class.recursiveregexiterator.php) 
- [RecursiveTreeIterator](https://www.php.net/manual/en/class.recursivetreeiterator.php)

<a name="json-pointer"></a>
### What is JSON Pointer anyway?
It's a way of addressing one item in JSON document. See the [JSON Pointer RFC 6901](https://tools.ietf.org/html/rfc6901).
It's very handy, because sometimes the JSON structure goes deeper, and you want to iterate a subtree,
not the main level. So you just specify the pointer to the JSON array or object (or even to a scalar value) you want to iterate and off you go.
When the parser hits the collection you specified, iteration begins. You can pass it as `pointer` option in all
`Items::from*` functions. If you specify a pointer to a non-existent position in the document, an exception is thrown.
It can be used to access scalar values as well. **JSON Pointer itself must be a valid JSON string**. Literal comparison
of reference tokens (the parts between slashes) is performed against the JSON document keys/member names.

Some examples:

| JSON Pointer value       | Will iterate through                                                                                      |
|--------------------------|-----------------------------------------------------------------------------------------------------------|
| (empty string - default) | `["this", "array"]` or `{"a": "this", "b": "object"}` will be iterated (main level)                       |
| `/result/items`          | `{"result": {"items": ["this", "array", "will", "be", "iterated"]}}`                                      |
| `/0/items`               | `[{"items": ["this", "array", "will", "be", "iterated"]}]` (supports array indices)                       |
| `/results/-/status`      | `{"results": [{"status": "iterated"}, {"status": "also iterated"}]}` (a hyphen as an array index wildcard)|
| `/` (gotcha! - a slash followed by an empty string, see the [spec](https://tools.ietf.org/html/rfc6901#section-5)) | `{"":["this","array","will","be","iterated"]}` |
| `/quotes\"`              | `{"quotes\"": ["this", "array", "will", "be", "iterated"]}`                                               |


<a name="options"></a>
## Options
Options may change how a JSON is parsed. Array of options is the second parameter of all `Items::from*` functions.
Available options are:
- `pointer` - A JSON Pointer string that tells which part of the document you want to iterate.
- `decoder` - An instance of `ItemDecoder` interface.
- `debug` - `true` or `false` to enable or disable the debug mode. When the debug mode is enabled, data such as line,
column and position in the document are available during parsing or in exceptions. Keeping debug disabled adds slight
performance advantage.

<a name="parsing-json-stream-api-responses"></a>
## Parsing streaming responses from a JSON API
A stream API response or any other JSON stream is parsed exactly the same way as file is. The only difference
is, you use `Items::fromStream($streamResource)` for it, where `$streamResource` is the stream
resource with the JSON document. The rest is the same as with parsing files. Here are some examples of
popular http clients which support streaming responses:

<a name="guzzlehttp"></a>
### GuzzleHttp
Guzzle uses its own streams, but they can be converted back to PHP streams by calling
`\GuzzleHttp\Psr7\StreamWrapper::getResource()`. Pass the result of this function to
`Items::fromStream` function, and you're set up. See working
[GuzzleHttp example](examples/guzzleHttp.php).

<a name="symfony-httpclient"></a>
### Symfony HttpClient
A stream response of Symfony HttpClient works as iterator. And because JSON Machine is
based on iterators, the integration with Symfony HttpClient is very simple. See
[HttpClient example](examples/symfonyHttpClient.php).


<a name="tracking-parsing-progress"></a>
## Tracking the progress (with `debug` enabled)
Big documents may take a while to parse. Call `Items::getPosition()` in your `foreach` to get current
count of the processed bytes from the beginning. Percentage is then easy to calculate as `position / total * 100`.
To find out the total size of your document in bytes you may want to check:
- `strlen($document)` if you parse a string
- `filesize($file)` if you parse a file
- `Content-Length` http header if you parse a http stream response
- ... you get the point

If `debug` is disabled, `getPosition()` always returns `0`.

```php
<?php

use JsonMachine\Items;

$fileSize = filesize('fruits.json');
$fruits = Items::fromFile('fruits.json', ['debug' => true]);
foreach ($fruits as $name => $data) {
    echo 'Progress: ' . intval($fruits->getPosition() / $fileSize * 100) . ' %'; 
}
```


<a name="decoders"></a>
## Decoders
`Items::from*` functions also accept `decoder` option. It must be an instance of
`JsonMachine\JsonDecoder\ItemDecoder`. If none is specified, `ExtJsonDecoder` is used by
default. It requires `ext-json` PHP extension to be present, because it uses
`json_decode`. When `json_decode` doesn't do what you want, implement `JsonMachine\JsonDecoder\ItemDecoder`
and make your own.

<a name="available-decoders"></a>
### Available decoders
- **`ExtJsonDecoder`** - **Default.** Uses `json_decode` to decode keys and values.
Constructor has the same parameters as `json_decode`.

- **`PassThruDecoder`** - Does no decoding. Both keys and values are produced as pure JSON strings.
Useful when you want to parse a JSON item with something else directly in the foreach
and don't want to implement `JsonMachine\JsonDecoder\ItemDecoder`. Since `1.0.0` does not use `json_decode`.

Example:
```php
<?php

use JsonMachine\JsonDecoder\PassThruDecoder;
use JsonMachine\Items;

$items = Items::fromFile('path/to.json', ['decoder' => new PassThruDecoder]);
```

- **`ErrorWrappingDecoder`** - A decorator which wraps decoding errors inside `DecodingError` object
thus enabling you to skip malformed items instead of dying on `SyntaxError` exception.
Example:
```php
<?php

use JsonMachine\Items;
use JsonMachine\JsonDecoder\DecodingError;
use JsonMachine\JsonDecoder\ErrorWrappingDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

$items = Items::fromFile('path/to.json', ['decoder' => new ErrorWrappingDecoder(new ExtJsonDecoder())]);
foreach ($items as $key => $item) {
    if ($key instanceof DecodingError || $item instanceof DecodingError) {
        // handle error of this malformed json item
        continue;
    }
    var_dump($key, $item);
}
```


<a name="error-handling"></a>
## Error handling
Since 0.4.0 every exception extends `JsonMachineException`, so you can catch that to filter any error from JSON Machine library.

<a name="malformed-items"></a>
### Skipping malformed items
If there's an error anywhere in a json stream, `SyntaxError` exception is thrown. That's very inconvenient,
because if there is an error inside one json item you are unable to parse the rest of the document
because of one malformed item. `ErrorWrappingDecoder` is a decoder decorator which can help you with that.
Wrap a decoder with it, and all malformed items you are iterating will be given to you in the foreach via
`DecodingError`. This way you can skip them and continue further with the document. See example in
[Available decoders](#available-decoders). Syntax errors in the structure of a json stream between the iterated
items will still throw `SyntaxError` exception though.


<a name="on-parser-efficiency"></a>
## Parser efficiency
The time complexity is always `O(n)`

<a name="streams-files"></a>
### Streams / files
TL;DR: The memory complexity is `O(2)`

JSON Machine reads a stream (or a file) 1 JSON item at a time and generates corresponding 1 PHP item at a time.
This is the most efficient way, because if you had say 10,000 users in JSON file and wanted to parse it using
`json_decode(file_get_contents('big.json'))`, you'd have the whole string in memory as well as all the 10,000
PHP structures. Following table shows the difference:

|                        | String items in memory at a time | Decoded PHP items in memory at a time | Total |
|------------------------|---------------------------------:|--------------------------------------:|------:|
| `json_decode()`        |                            10000 |                                 10000 | 20000 |
| `Items::from*()`       |                                1 |                                     1 |     2 |

This means, that JSON Machine is constantly efficient for any size of processed JSON. 100 GB no problem.

<a name="in-memory-json-strings"></a>
### In-memory JSON strings
TL;DR: The memory complexity is `O(n+1)`

There is also a method `Items::fromString()`. If you are
forced to parse a big string, and the stream is not available, JSON Machine may be better than `json_decode`.
The reason is that unlike `json_decode`, JSON Machine still traverses the JSON string one item at a time and doesn't
load all resulting PHP structures into memory at once.

Let's continue with the example with 10,000 users. This time they are all in string in memory.
When decoding that string with `json_decode`, 10,000 arrays (objects) is created in memory and then the result
is returned. JSON Machine on the other hand creates single structure for each found item in the string and yields it back
to you. When you process this item and iterate to the next one, another single structure is created. This is the same
behaviour as with streams/files. Following table puts the concept into perspective:

|                             | String items in memory at a time | Decoded PHP items in memory at a time | Total |
|-----------------------------|---------------------------------:|--------------------------------------:|------:|
| `json_decode()`             |                            10000 |                                 10000 | 20000 |
| `Items::fromString()`       |                            10000 |                                     1 | 10001 |

The reality is even better. `Items::fromString` consumes about **5x less memory** than `json_decode`. The reason is
that a PHP structure takes much more memory than its corresponding JSON representation.


<a name="troubleshooting"></a>
## Troubleshooting

<a name="step1"></a>
### "I'm still getting Allowed memory size ... exhausted"
One of the reasons may be that the items you want to iterate over are in some sub-key such as `"results"`
but you forgot to specify a JSON Pointer. See [Parsing a subtree](#parsing-a-subtree).

<a name="step2"></a>
### "That didn't help"
The other reason may be, that one of the items you iterate is itself so huge it cannot be decoded at once.
For example, you iterate over users and one of them has thousands of "friend" objects in it.
The most efficient solution is to use [Recursive iteration](#recursive).

<a name="step3"></a>
### "I am still out of luck"
It probably means that a single JSON scalar string itself is too big to fit in memory.
For example very big base64-encoded file.
In that case you will probably be still out of luck until JSON Machine supports yielding of scalar values as PHP streams.

<a name="installation"></a>
## Installation

### Using Composer
```bash
composer require halaxa/json-machine
```

### Without Composer
Clone or download this repository and add the following to your bootstrap file:
```php
spl_autoload_register(require '/path/to/json-machine/src/autoloader.php');
```

<a name="development"></a>
## Development
Clone this repository. This library supports two development approaches:
1. non containerized (PHP and composer already installed on your machine)
1. containerized (Docker on your machine)

<a name="non-containerized"></a>
### Non containerized
Run `composer run -l` in the project dir to see available dev scripts. This way you can run some steps
of the build process such as tests.

<a name="containerized"></a>
### Containerized
[Install Docker](https://docs.docker.com/install/) and run `make` in the project dir on your host machine
to see available dev tools/commands. You can run all the steps of the build process separately as well
as the whole build process at once. Make basically runs composer dev scripts inside containers in the background.

`make build`: Runs complete build. The same command is run via GitHub Actions CI.



<a name="support"></a>
## Support
Do you like this library? Star it, share it, show it  :)
Issues and pull requests are very welcome.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/G2G57KTE4)

<a name="license"></a>
## License
Apache 2.0

Cogwheel element: Icons made by [TutsPlus](https://www.flaticon.com/authors/tutsplus)
from [www.flaticon.com](https://www.flaticon.com/)
is licensed by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/)

<i><a href='http://ecotrust-canada.github.io/markdown-toc/'>Table of contents generated with markdown-toc</a></i>
