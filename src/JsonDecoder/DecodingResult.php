<?php

namespace JsonMachine\JsonDecoder;

@trigger_error(sprintf(
    'Class %s is deprecated. Use %s instead.',
    DecodingResult::class,
    ChunkDecodingResult::class
), E_USER_DEPRECATED);

/**
 * @deprecated Use ChunkDecodingResult instead.
 */
class DecodingResult
{
    private $isOk;
    private $value;
    private $errorMessage;

    /**
     * DecodingResult constructor.
     * @param bool $isOk
     * @param mixed $value
     * @param string $errorMessage
     */
    public function __construct($isOk, $value, $errorMessage = null)
    {
        $this->isOk = $isOk;
        $this->value = $value;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return bool
     */
    public function isOk()
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
