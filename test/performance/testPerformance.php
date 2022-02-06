<?php

declare(strict_types=1);

use JsonMachine\Items;

require_once __DIR__.'/../../vendor/autoload.php';

if (in_array('xdebug', get_loaded_extensions())) {
    trigger_error('Xdebug enabled. Results may be affected.', E_USER_WARNING);
}

ini_set('memory_limit', '-1'); // for json_decode use case

$decoders = [
    'Items::fromFile()' => function ($file) {
        return Items::fromFile($file);
    },
    'Items::fromString()' => function ($file) {
        return Items::fromString(stream_get_contents(fopen($file, 'r')));
    },
    'Items::fromFile() - debug' => function ($file) {
        return Items::fromFile($file, ['debug' => true]);
    },
    'Items::fromString() - debug' => function ($file) {
        return Items::fromString(stream_get_contents(fopen($file, 'r')), ['debug' => true]);
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
