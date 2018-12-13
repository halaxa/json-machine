<?php

use JsonMachine\JsonMachine;
use JsonMachine\Lexer;
use JsonMachine\Parser;
use JsonMachine\StreamBytes;

require_once __DIR__ . '/../../vendor/autoload.php';

testPerformanceJsonMachineInMemory();
testPerformanceJsonDecode();
testPerformanceJsonMachine();

function testPerformanceJsonMachine()
{
    $tmpJsonFileName = createBigJsonFile();
    $tmpJson = fopen($tmpJsonFileName, 'r');
    $parser = new Parser(new Lexer(new StreamBytes($tmpJson)));
    $start = microtime(true);
    foreach ($parser as $item) {

    }
    $time = microtime(true) - $start;
    $filesizeMb = (filesize($tmpJsonFileName)/1024/1024);
    echo "JsonMachine::fromStream: ". round($filesizeMb/$time, 2) . 'Mb/s'.PHP_EOL;
    @unlink($tmpJsonFileName);
}

function testPerformanceJsonMachineInMemory()
{
    $tmpJsonFileName = createBigJsonFile();
    $tmpJson = file_get_contents($tmpJsonFileName);
    $start = microtime(true);
    foreach (JsonMachine::fromString($tmpJson) as $item) {

    }
    $time = microtime(true) - $start;
    $filesizeMb = (filesize($tmpJsonFileName)/1024/1024);
    echo "JsonMachine::fromString: ". round($filesizeMb/$time, 2) . 'Mb/s'.PHP_EOL;
    @unlink($tmpJsonFileName);
}

function testPerformanceJsonDecode()
{
    $tmpJsonFileName = createBigJsonFile();
    $tmpJson = file_get_contents($tmpJsonFileName);
    $start = microtime(true);
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


