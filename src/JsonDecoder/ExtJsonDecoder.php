<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class ExtJsonDecoder implements ItemDecoder
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

    public function decode($jsonValue)
    {
        $decoded = json_decode($jsonValue, $this->assoc, $this->depth, $this->options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new InvalidResult(json_last_error_msg());
        }

        return new ValidResult($decoded);
    }
}
