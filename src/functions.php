<?php

namespace JsonMachine;

use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @param iterable $iterable
 * @return \Generator
 */
function objects($iterable)
{
    foreach ($iterable as $item) {
        yield (object) $item;
    }
}

function httpClientChunks(ResponseStreamInterface $responseStream)
{
    foreach ($responseStream as $chunk) {
        yield $chunk->getContent();
    }
}
