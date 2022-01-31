<?php

namespace JsonMachine\JsonDecoder;

interface ItemDecoder
{
    /**
     * Decodes keys which are directly yielded to the user.
     *
     * @return InvalidResult|ValidResult
     */
    public function decodeKey($jsonScalarKey);

    /**
     * Decodes composite or scalar values which are directly yielded to the user.
     *
     * @return InvalidResult|ValidResult
     */
    public function decodeValue($jsonValue);
}
