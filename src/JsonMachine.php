<?php

namespace JsonMachine;

use IteratorAggregate;
use JsonMachine\Exception\InvalidArgumentException;

class JsonMachine implements IteratorAggregate
{
    /**
     * @var \Iterator|\IteratorAggregate
     */
    private $bytesIterator;

    /**
     * @var string
     */
    private $jsonPointer;

    /**
     * JsonMachine constructor.
     * @param \Iterator|\IteratorAggregate $bytesIterator
     * @param string $jsonPointer
     */
    public function __construct($bytesIterator, $jsonPointer = '')
    {
        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromString($string, $jsonPointer = '')
    {
        return new static(new StreamBytes(fopen("data://text/plain,$string", 'r')), $jsonPointer);
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromFile($file, $jsonPointer = '')
    {
        return new static(new StreamBytes(fopen($file, 'r')), $jsonPointer);
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @return self
     */
    public static function fromStream($stream, $jsonPointer = '')
    {
        return new static(new StreamBytes($stream), $jsonPointer);
    }

    public function getIterator()
    {
        return new Parser(new Lexer($this->bytesIterator), $this->jsonPointer);
    }
}
