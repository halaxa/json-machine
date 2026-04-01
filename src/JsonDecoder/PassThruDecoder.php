<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class PassThruDecoder implements ItemDecoder
{
    /**
     * @param mixed $jsonValue
     *
     * @return ValidResult
     */
    public function decode($jsonValue)
    {
        return new ValidResult($jsonValue);
    }
}
