<?php

namespace JsonMachine;

use JsonMachine\JsonDecoder\Decoder;

class JsonMachine implements \IteratorAggregate, PositionAware
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
     * @var Decoder|null
     */
    private $jsonDecoder;

    /**
     * @var iterable
     */
    private $parser;

    /**
     * @param $bytesIterator
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     */
    public function __construct($bytesIterator, $jsonPointer = '', $jsonDecoder = null)
    {
        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
        $this->jsonDecoder = $jsonDecoder;

        $this->parser = new Parser(new Lexer($this->bytesIterator), $this->jsonPointer, $this->jsonDecoder);
    }

    /**
     * @param $string
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @return self
     */
    public static function fromString($string, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new StringChunks($string), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param string $file
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @return self
     */
    public static function fromFile($file, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new FileChunks($file), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param resource $stream
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @return self
     */
    public static function fromStream($stream, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new StreamChunks($stream), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param \Traversable|array $iterable
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @return self
     */
    public static function fromIterable($iterable, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static($iterable, $jsonPointer, $jsonDecoder);
    }

    public function getIterator()
    {
        return $this->parser;
    }

    public function getPosition()
    {
        return $this->parser->getPosition();
    }
}
