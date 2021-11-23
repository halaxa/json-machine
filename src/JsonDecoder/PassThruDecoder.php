<?php

namespace JsonMachine\JsonDecoder;

use JsonMachine\JsonDecoder\Decoder;

class PassThruDecoder implements Decoder
{
    use JsonDecodingTrait;

    public function decodeValue($jsonValue)
    {
        return new DecodingResult(true, $jsonValue);
    }
}
