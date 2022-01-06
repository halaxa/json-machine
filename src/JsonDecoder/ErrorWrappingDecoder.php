<?php

namespace JsonMachine\JsonDecoder;

class ErrorWrappingDecoder implements ItemDecoder
{
    /**
     * @var Decoder
     */
    private $innerDecoder;

    public function __construct(ItemDecoder $innerDecoder)
    {
        $this->innerDecoder = $innerDecoder;
    }

    public function decodeKey($jsonScalarKey)
    {
        $result = $this->innerDecoder->decodeKey($jsonScalarKey);
        if (! $result->isOk()) {
            return new ValidResult(new DecodingError($jsonScalarKey, $result->getErrorMessage()));
        }
        return $result;
    }

    public function decodeValue($jsonValue)
    {
        $result = $this->innerDecoder->decodeValue($jsonValue);
        if (! $result->isOk()) {
            return new ValidResult(new DecodingError($jsonValue, $result->getErrorMessage()));
        }
        return $result;
    }

    public function decodeInternalKey($jsonScalarKey)
    {
        return $this->innerDecoder->decodeInternalKey($jsonScalarKey);
    }
}
