<?php

namespace JsonMachine\JsonDecoder;

@trigger_error(sprintf(
    'Class %s is deprecated. Use one of %s, %s or %s instead.',
    DecodingResult::class,
    ValidResult::class,
    ValidStringResult::class,
    InvalidResult::class
), E_USER_DEPRECATED);

/**
 * @deprecated
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
     * @deprecated
     * @return bool
     */
    public function isOk()
    {
        return $this->isOk;
    }

    /**
     * @deprecated
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @deprecated
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
