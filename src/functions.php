<?php

namespace JsonMachine;

use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @deprecated Function 'objects' is deprecated in favor of custom decoders. Use ExtJsonDecoder instead
 * @param iterable $iterable
 * @return \Generator
 */
function objects($iterable)
{
    trigger_error("Function 'objects' is deprecated in favor of custom decoders. Use ExtJsonDecoder instead", E_USER_DEPRECATED);
    foreach ($iterable as $item) {
        yield (object) $item;
    }
}

/**
 * @deprecated Function 'httpClientChunks' is deprecated and will be removed. Please make your own
 * @param ResponseStreamInterface $responseStream
 * @return \Generator
 */
function httpClientChunks(ResponseStreamInterface $responseStream)
{
    trigger_error("Function 'httpClientChunks' is deprecated and will be removed. Please make your own.", E_USER_DEPRECATED);
    foreach ($responseStream as $chunk) {
        yield $chunk->getContent();
    }
}
