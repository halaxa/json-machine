<?php

use JsonMachine\JsonMachine;
use Symfony\Component\HttpClient\HttpClient;
use function JsonMachine\httpClientChunks;

require_once __DIR__ . '/../../vendor/autoload.php';

$client = HttpClient::create();
$response = $client->request('GET', 'https://httpbin.org/anything?key=value');
$jsonChunks = httpClientChunks($client->stream($response));
foreach (JsonMachine::fromIterable($jsonChunks, "/args") as $key => $value) {
    var_dump($key, $value);
}

