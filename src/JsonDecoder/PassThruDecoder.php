<?php

namespace JsonMachine\JsonDecoder;

class PassThruDecoder implements ChunkDecoder
{
    use ExtJsonDecoding;

    public function decodeValue($jsonValue)
    {
        return new ValidResult($jsonValue);
    }
}
