<?php

namespace JsonMachine;

use IteratorAggregate;
use JsonMachine\Exception\InvalidArgumentException;

class JsonMachine implements IteratorAggregate
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var string
     */
    private $jsonPointer;

    public function __construct($stream, $jsonPointer = '')
    {
        if ( ! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException("Argument \$stream must be a valid stream resource.");
        }
        $this->stream = $stream;
        $this->jsonPointer = $jsonPointer;
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromString($string, $jsonPointer = '')
    {
        return new static(fopen("data://text/plain,$string", 'r'), $jsonPointer);
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromFile($file, $jsonPointer = '')
    {
        return new static(fopen($file, 'r'), $jsonPointer);
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromStream($stream, $jsonPointer = '')
    {
        return new static($stream, $jsonPointer);
    }

    public function getIterator()
    {
        return new Parser(new Lexer($this->stream), $this->jsonPointer);
    }
}
