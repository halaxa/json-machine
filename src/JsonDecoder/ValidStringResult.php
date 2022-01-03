<?php

namespace JsonMachine\JsonDecoder;

class ValidStringResult
{
    /**
     * @var mixed
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isOk(): bool
    {
        return true;
    }
}
