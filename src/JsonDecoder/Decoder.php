<?php

namespace JsonMachine\JsonDecoder;

interface Decoder
{
    /**
     * @param string $jsonScalarKey
     * @return DecodingResult
     */
    public function decodeKey($jsonScalarKey);

    /**
     * @param string $jsonValue
     * @return DecodingResult
     */
    public function decodeValue($jsonValue);
}
