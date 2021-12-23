<?php


namespace JsonMachine\JsonDecoder;


interface ChunkDecoder
{
    /**
     * @return InvalidResult|ValidStringResult
     */
    public function decodeInternalKey($jsonScalarKey);

    /**
     * @return InvalidResult|ValidResult
     */
    public function decodeKey($jsonScalarKey);

    /**
     * @return InvalidResult|ValidResult
     */
    public function decodeValue($jsonValue);
}
