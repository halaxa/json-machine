<?php

namespace JsonMachine;

use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @param iterable $iterable
 * @return \Generator
 */
function objects($iterable)
{
    @trigger_error("Function 'objects' is deprecated in favor of custom decoders.", E_USER_DEPRECATED);
    foreach ($iterable as $item) {
        yield (object) $item;
    }
}

function httpClientChunks(ResponseStreamInterface $responseStream)
{
    @trigger_error("Function 'httpClientChunks' is deprecated and will be removed. Please make your own.", E_USER_DEPRECATED);
    foreach ($responseStream as $chunk) {
        yield $chunk->getContent();
    }
}
