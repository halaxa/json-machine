<?php

namespace JsonMachine\JsonDecoder;

class ChunkDecodingStringResult
{
    /**
     * @var bool
     */
    private $isOk;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string|null
     */
    private $errorMessage;

    public function __construct(bool $isOk, string $value, string $errorMessage = null)
    {
        $this->isOk = $isOk;
        $this->value = $value;
        $this->errorMessage = $errorMessage;
    }

    public function isOk(): bool
    {
        return $this->isOk;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
