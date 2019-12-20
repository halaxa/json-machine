<?php

namespace JsonMachine;

class JsonMachine implements \IteratorAggregate
{
    /**
     * @var \Traversable
     */
    private $bytesIterator;

    /**
     * @var string
     */
    private $jsonPointer;

    /**
     * @param \Traversable|array
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
        return new static(new StringBytes($string), $jsonPointer);
    }

    /**
     * @param string $file
     * @param string $jsonPointer
     * @return self
     */
    public static function fromFile($file, $jsonPointer = '')
    {
        return new static(new StreamBytes(fopen($file, 'r')), $jsonPointer);
    }

    /**
     * @param resource $stream
     * @param string $jsonPointer
     * @return self
     */
    public static function fromStream($stream, $jsonPointer = '')
    {
        return new static(new StreamBytes($stream), $jsonPointer);
    }

    /**
     * @param \Traversable|array $iterable
     * @param string $jsonPointer
     * @return self
     */
    public static function fromIterable($iterable, $jsonPointer = '')
    {
        return new static($iterable, $jsonPointer);
    }

    public function getIterator()
    {
        return new Parser(new Lexer($this->bytesIterator), $this->jsonPointer);
    }
}
