<?php

use JsonIterator\Lexer;
use JsonIterator\Parser;

require_once __DIR__ . '/../../vendor/autoload.php';

testPerformanceJsonIterator();
testPerformanceJsonDecode();

function testPerformanceJsonIterator()
{
    $tmpJsonFileName = createBigJsonFile();
    $tmpJson = fopen($tmpJsonFileName, 'r');
    $parser = new Parser(new Lexer($tmpJson));
    $start = microtime(true);
    foreach ($parser as $item) {

    }
    $time = microtime(true) - $start;
    $filesizeMb = (filesize($tmpJsonFileName)/1024/1024);
    echo "JsonIterator: ". round($filesizeMb/$time, 2) . 'Mb/s'.PHP_EOL;
    @unlink($tmpJsonFileName);
}

function testPerformanceJsonDecode()
{
    $tmpJsonFileName = createBigJsonFile();
    $start = microtime(true);
    $tmpJson = file_get_contents($tmpJsonFileName);
    json_decode($tmpJson);
    $time = microtime(true) - $start;
    $filesizeMb = (filesize($tmpJsonFileName)/1024/1024);
    echo "json_decode: ". round($filesizeMb/$time, 2) . 'Mb/s'.PHP_EOL;
    @unlink($tmpJsonFileName);
}

function createBigJsonFile()
{
    $tmpJson = tempnam(sys_get_temp_dir(), 'json_');
    $f = fopen($tmpJson, 'w');
    $separator = '';
    fputs($f, '[');
    for ($i=0; $i<1000; $i++) {
        fputs($f, $separator);
        fputs($f, file_get_contents(__DIR__.'/twitter_example_'. ($i%2) .'.json'));
        $separator = ",\n\n";
    }
    fputs($f, ']');
    fclose($f);
    return $tmpJson;
}


