<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class ValidResult
{
    /**
     * @var mixed
     */
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isOk(): bool
    {
        return true;
    }
}
