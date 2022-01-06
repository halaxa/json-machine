<?php

namespace JsonMachine\JsonDecoder;


class ExtJsonDecoder implements ItemDecoder
{
    use ExtJsonDecoding;

    public function decodeValue($jsonValue)
    {
        // inlined
        $decoded = json_decode($jsonValue, $this->assoc, $this->depth, $this->options);
        if ($decoded === null && $jsonValue !== 'null') {
            return new InvalidResult(json_last_error_msg());
        }
        return new ValidResult($decoded);
    }
}
