<?php

use JsonMachine\JsonMachine;

require_once __DIR__ . '/../../vendor/autoload.php';

$decoders = [
    'JsonMachine::fromFile()' => function($file) {
        return JsonMachine::fromFile($file);
    },
    'JsonMachine::fromString()' => function($file) {
        return JsonMachine::fromString(stream_get_contents(fopen($file, 'r')));
    },
    'json_decode()' => function($file) {
        return json_decode(stream_get_contents(fopen($file, 'r')), true);
    },
];

$tmpJsonFileName = createBigJsonFile();
$fileSizeMb = (filesize($tmpJsonFileName)/1024/1024);
echo round($fileSizeMb, 2)," MB".PHP_EOL;
foreach ($decoders as $name => $decoder) {
    $start = microtime(true);
    $result = $decoder($tmpJsonFileName);
    if ( ! $result instanceof \Traversable && ! is_array($result)) {
        $textResult = "Decoding error";
    } else {
        foreach ($result as $item) {

        }
        $time = microtime(true) - $start;
        $textResult = round($fileSizeMb/$time, 2) . ' MB/s';
    }

    echo "$name: $textResult".PHP_EOL;
}
@unlink($tmpJsonFileName);

function createBigJsonFile()
{
    $tmpJson = tempnam(sys_get_temp_dir(), 'json_');
    $f = fopen($tmpJson, 'w');
    $separator = '';
    fputs($f, '[');
    for ($i=0; $i<2000; $i++) {
        fputs($f, $separator);
        fputs($f, file_get_contents(__DIR__.'/twitter_example_'. ($i%2) .'.json'));
        $separator = ",\n\n";
    }
    fputs($f, ']');
    fclose($f);
    return $tmpJson;
}
