<?php


namespace JsonMachine\JsonDecoder;


interface ChunkDecoder
{
    public function decodeInternalKey($jsonScalarKey): ChunkDecodingStringResult;

    public function decodeKey($jsonScalarKey): ChunkDecodingResult;

    public function decodeValue($jsonValue): ChunkDecodingResult;
}
