<?php

declare(strict_types=1);

use JsonMachine\ExtTokens;
use JsonMachine\FileChunks;
use JsonMachine\Items;
use JsonMachine\Parser;

require_once __DIR__.'/../../vendor/autoload.php';

passthru('php -v');
echo "Ext jsonmachine version: ". phpversion('jsonmachine') . PHP_EOL;
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
    'Items::fromFile()' => function ($file) {
        return Items::fromFile($file);
    },
    'Items::fromFile() - debug' => function ($file) {
        return Items::fromFile($file, ['debug' => true]);
    },
    'Items::fromFile() - ext' => function ($file) {
        return new Parser(new ExtTokens((new FileChunks($file))->getIterator()));
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

    echo str_pad($name.': ', 37, '.')." $textResult".PHP_EOL;
}
@unlink($tmpJsonFileName);

function createBigJsonFile()
{
    $tmpJson = tempnam(sys_get_temp_dir(), 'json_');
    $f = fopen($tmpJson, 'w');
    $separator = '';
    fputs($f, '[');
    for ($i = 0; $i < 6000; ++$i) {
        fputs($f, $separator);
        fputs($f, file_get_contents(__DIR__.'/twitter_example_'.($i % 2).'.json'));
        $separator = ",\n\n";
    }
    fputs($f, ']');
    fclose($f);

    return $tmpJson;
}
