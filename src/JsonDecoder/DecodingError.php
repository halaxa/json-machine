<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class DecodingError
{
    private $malformedJson;
    private $errorMessage;

    /**
     * @param string $malformedJson
     * @param string $errorMessage
     */
    public function __construct($malformedJson, $errorMessage)
    {
        $this->malformedJson = $malformedJson;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string
     */
    public function getMalformedJson()
    {
        return $this->malformedJson;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
