<?php

namespace JsonMachine\JsonDecoder;

trait ExtJsonDecoding
{
    /**
     * @var bool
     */
    private $assoc;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var int
     */
    private $options;

    public function __construct($assoc = false, $depth = 512, $options = 0)
    {
        $this->assoc = $assoc;
        $this->depth = $depth;
        $this->options = $options;
    }


    public function decodeKey($jsonScalarKey)
    {
        // inlined
        $decoded = json_decode($jsonScalarKey, $this->assoc, $this->depth, $this->options);
        if ($decoded === null && $jsonScalarKey !== 'null') {
            return new InvalidResult(json_last_error_msg());
        }
        return new ValidResult($decoded);
    }

    public function decodeInternalKey($jsonScalarKey)
    {
        // inlined
        $decoded = json_decode($jsonScalarKey, $this->assoc, $this->depth, $this->options);
        if ($decoded === null && $jsonScalarKey !== 'null') {
            return new InvalidResult(json_last_error_msg());
        }
        return new ValidStringResult($decoded);
    }
}
