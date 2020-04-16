<?php


namespace JsonMachine\JsonDecoder;


use JsonMachine\JsonDecoder\Decoder;

class PassThruDecoder implements Decoder
{
    public function decodeKey($jsonScalarKey)
    {
        $decoded = json_decode($jsonScalarKey, true);
        if ($decoded === null && $jsonScalarKey !== 'null') {
            return new DecodingResult(false, null, json_last_error_msg());
        }
        return new DecodingResult(true, $decoded);
    }

    public function decodeValue($jsonValue)
    {
        return new DecodingResult(true, $jsonValue);
    }
}
