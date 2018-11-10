<?php

namespace JsonIterator;

class DecoderError
{
    private $errorMessage = '';

    private $malformedJson = '';

    /**
     * @param string $malformedJson
     * @param string $errorMessage
     */
    public function __construct($errorMessage, $malformedJson)
    {
        $this->malformedJson = $malformedJson;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return string
     */
    public function getMalformedJson()
    {
        return $this->malformedJson;
    }
}
