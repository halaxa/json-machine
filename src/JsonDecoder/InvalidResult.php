<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class InvalidResult
{
    /**
     * @var string
     */
    private $errorMessage;

    public function __construct(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function isOk(): bool
    {
        return false;
    }
}
