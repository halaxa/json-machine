<?php

namespace JsonMachine\JsonDecoder;

class ChunkDecodingResult
{
    /**
     * @var bool
     */
    private $isOk;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string|null
     */
    private $errorMessage;

    public function __construct(bool $isOk, $value, string $errorMessage = null)
    {
        $this->isOk = $isOk;
        $this->value = $value;
        $this->errorMessage = $errorMessage;
    }

    public function isOk(): bool
    {
        return $this->isOk;
    }

    /**
     * @return mixed
     */
    public function getValue()
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
