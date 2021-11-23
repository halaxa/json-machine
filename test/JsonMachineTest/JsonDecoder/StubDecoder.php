<?php

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\Decoder;

class StubDecoder implements Decoder
{
    private $decodedKey;
    private $decodedValue;

    public function __construct($decodedKey, $decodedValue)
    {
        $this->decodedKey = $decodedKey;
        $this->decodedValue = $decodedValue;
    }

    public function decodeKey($jsonScalarKey)
    {
        return $this->decodedKey;
    }

    public function decodeValue($jsonValue)
    {
        return $this->decodedValue;
    }
}
