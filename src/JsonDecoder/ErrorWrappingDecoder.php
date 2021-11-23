<?php

namespace JsonMachine\JsonDecoder;

class ErrorWrappingDecoder implements Decoder
{
    /**
     * @var Decoder
     */
    private $innerDecoder;

    public function __construct(Decoder $innerDecoder)
    {
        $this->innerDecoder = $innerDecoder;
    }

    public function decodeKey($jsonScalarKey)
    {
        $result = $this->innerDecoder->decodeKey($jsonScalarKey);
        if (! $result->isOk()) {
            return new DecodingResult(true, new DecodingError($jsonScalarKey, $result->getErrorMessage()));
        }
        return $result;
    }

    public function decodeValue($jsonValue)
    {
        $result = $this->innerDecoder->decodeValue($jsonValue);
        if (! $result->isOk()) {
            return new DecodingResult(true, new DecodingError($jsonValue, $result->getErrorMessage()));
        }
        return $result;
    }
}
