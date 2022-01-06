<?php

namespace JsonMachine\JsonDecoder;

@trigger_error(sprintf(
    'Interface %s is deprecated. Use %s instead.',
    Decoder::class,
    ItemDecoder::class
), E_USER_DEPRECATED);

/**
 * @deprecated Use ItemDecoder instead.
 */
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
