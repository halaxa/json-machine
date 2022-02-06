<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\ItemDecoder;

class StubDecoder implements ItemDecoder
{
    private $decoded;

    public function __construct($decoded)
    {
        $this->decoded = $decoded;
    }

    public function decode($jsonValue)
    {
        return $this->decoded;
    }
}
