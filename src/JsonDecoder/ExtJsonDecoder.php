<?php


namespace JsonMachine\JsonDecoder;


use JsonMachine\JsonDecoder\Decoder;

class ExtJsonDecoder implements Decoder
{
    public function decodeKey($jsonScalarKey)
    {
        // inlined
        $decoded = json_decode($jsonScalarKey, true);
        if ($decoded === null && $jsonScalarKey !== 'null') {
            return new DecodingResult(false, null, json_last_error_msg());
        }
        return new DecodingResult(true, $decoded);
    }

    public function decodeValue($jsonValue)
    {
        // inlined
        $decoded = json_decode($jsonValue, true);
        if ($decoded === null && $jsonValue !== 'null') {
            return new DecodingResult(false, null, json_last_error_msg());
        }
        return new DecodingResult(true, $decoded);
    }
}
