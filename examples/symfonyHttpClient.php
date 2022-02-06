<?php

declare(strict_types=1);

use JsonMachine\Items;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

require_once __DIR__.'/../../vendor/autoload.php';

function httpClientChunks(ResponseStreamInterface $responseStream)
{
    foreach ($responseStream as $chunk) {
        yield $chunk->getContent();
    }
}

$client = HttpClient::create();
$response = $client->request('GET', 'https://httpbin.org/anything?key=value');
$jsonChunks = httpClientChunks($client->stream($response));
foreach (Items::fromIterable($jsonChunks, ['pointer' => '/args']) as $key => $value) {
    var_dump($key, $value);
}
