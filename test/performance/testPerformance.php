<?php

declare(strict_types=1);

use JsonMachine\FileChunks;
use JsonMachine\Items;
use JsonMachine\RecursiveItems;
use JsonMachine\Tokens;

require_once __DIR__.'/../../vendor/autoload.php';

if ( ! ini_get('xdebug.mode')) {
    echo "Xdebug disabled\n";
} else {
    echo "Xdebug enabled\n";
}

if ( ! function_exists('opcache_get_status')) {
    echo "Opcache disabled\n";
    echo "JIT disabled\n";
} else {
    echo "Opcache enabled\n";
    if (opcache_get_status()['jit']['enabled']) {
        echo "JIT enabled\n";
    } else {
        echo "JIT disabled\n";
    }
}

ini_set('memory_limit', '-1'); // for json_decode use case

$decoders = [
    'Tokens' => function ($file) {
        return new Tokens(
            new FileChunks($file)
        );
    },
    'Items' => function ($file) {
        return Items::fromFile($file);
    },
    'Items - debug' => function ($file) {
        return Items::fromFile($file, ['debug' => true]);
    },
    'RecursiveItems' => function ($file) {
        return RecursiveItems::fromFile($file);
    },
    'json_decode()' => function ($file) {
        return json_decode(stream_get_contents(fopen($file, 'r')), true);
    },
];

$tmpJsonFileName = createBigJsonFile();
$fileSizeMb = (filesize($tmpJsonFileName) / 1024 / 1024);
echo 'File size: '.round($fileSizeMb, 2),' MB'.PHP_EOL;
foreach ($decoders as $name => $decoder) {
    $start = microtime(true);
    $result = $decoder($tmpJsonFileName);
    if ( ! $result instanceof \Traversable && ! is_array($result)) {
        $textResult = 'Decoding error';
    } else {
        foreach ($result as $key => $item) {
        }
        $time = microtime(true) - $start;
        $textResult = round($fileSizeMb / $time, 2).' MB/s';
    }

    echo str_pad($name.': ', 25, '.')." $textResult".PHP_EOL;
}
@unlink($tmpJsonFileName);

function createBigJsonFile()
{
    $tmpJson = tempnam(sys_get_temp_dir(), 'json_');
    $f = fopen($tmpJson, 'w');
    $separator = '';
    fputs($f, '[');
//    for ($i = 0; $i < 1; ++$i) {
    for ($i = 0; $i < 3000; ++$i) {
        fputs($f, $separator);
        fputs($f, file_get_contents(__DIR__.'/twitter_example_'.($i % 2).'.json'));
        $separator = ",\n\n";
    }
    fputs($f, ']');
    fclose($f);

    return $tmpJson;
}
