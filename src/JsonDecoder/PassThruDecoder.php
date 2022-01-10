<?php

namespace JsonMachine\JsonDecoder;

class PassThruDecoder implements ItemDecoder
{
    use ExtJsonDecoding;

    public function decodeValue($jsonValue)
    {
        return new ValidResult($jsonValue);
    }
}
