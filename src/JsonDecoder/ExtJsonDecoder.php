<?php

namespace JsonMachine\JsonDecoder;

use JsonMachine\JsonDecoder\Decoder;

class ExtJsonDecoder implements Decoder
{
    use JsonDecodingTrait;

    public function decodeValue($jsonValue)
    {
        // inlined
        $decoded = json_decode($jsonValue, $this->assoc, $this->depth, $this->options);
        if ($decoded === null && $jsonValue !== 'null') {
            return new DecodingResult(false, null, json_last_error_msg());
        }
        return new DecodingResult(true, $decoded);
    }
}
