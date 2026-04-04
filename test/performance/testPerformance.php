<?php

declare(strict_types=1);

use JsonMachine\FileChunks;
use JsonMachine\Items;
use JsonMachine\RecursiveItems;
use JsonMachine\Tokens;

require_once __DIR__.'/../../vendor/autoload.php';

if ( ! ini_get('xdebug.mode')) {
    echo "✓ Xdebug disabled\n";
} else {
    echo "✗ Xdebug enabled\n";
}

if ( ! function_exists('opcache_get_status')) {
    echo "✗ Opcache disabled\n";
    echo "✗ JIT disabled\n";
} else {
    echo "✓ Opcache enabled\n";
    if (opcache_get_status()['jit']['enabled']) {
        echo "✓ JIT enabled\n";
    } else {
        echo "✗ JIT disabled\n";
    }
}

ini_set('memory_limit', '-1'); // for json_decode use case

$decoders = [
    'Tokens' => function ($file) {
        foreach (new Tokens(
            new FileChunks($file)
        ) as $k => $v) {}
    },
    'Items' => function ($file) {
        foreach (Items::fromFile($file) as $k => $v) {}
    },
    'Items - debug' => function ($file) {
        foreach (Items::fromFile($file, ['debug' => true]) as $k => $v) {}
    },
    'RecursiveItems' => function ($file) {
        foreach (RecursiveItems::fromFile($file) as $k => $v) {}
    },
    'json_decode()' => function ($file) {
        json_decode(stream_get_contents(fopen($file, 'r')), true);
    },
];

$tmpJsonFileName = createBigJsonFile();
$fileSizeMb = (filesize($tmpJsonFileName) / 1024 / 1024);
echo 'File size: '.round($fileSizeMb, 2),' MB'.PHP_EOL;
foreach ($decoders as $name => $decoder) {
    $start = microtime(true);
    $result = $decoder($tmpJsonFileName);
    $time = microtime(true) - $start;

    $textResult = round($fileSizeMb / $time, 2).' MB/s';
    echo str_pad($name.': ', 25, '.')." $textResult".PHP_EOL;
}
@unlink($tmpJsonFileName);

function createBigJsonFile()
{
    $tmpJson = tempnam(sys_get_temp_dir(), 'json_');
    $f = fopen($tmpJson, 'w');
    fputs($f, '[');
    for ($i = 0; $i < 3000; ++$i) {
        fputs($f, $i > 0 ? ",\n\n" : '');
        fputs($f, file_get_contents(__DIR__.'/twitter_example_'.($i % 2).'.json'));
    }
    fputs($f, ']');
    fclose($f);

    return $tmpJson;
}
