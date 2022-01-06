<?php

namespace JsonMachine;

use JsonMachine\JsonDecoder\ItemDecoder;
use JsonMachine\JsonDecoder\Decoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

/**
 * @deprecated Use class Items instead
 */
class JsonMachine implements \IteratorAggregate, PositionAware
{
    /**
     * @var iterable
     */
    private $bytesIterator;

    /**
     * @var string
     */
    private $jsonPointer;

    /**
     * @var Decoder|ItemDecoder|null
     */
    private $jsonDecoder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param iterable $bytesIterator
     * @param string $jsonPointer
     * @param Decoder|ItemDecoder $jsonDecoder
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($bytesIterator, $jsonPointer = '', $jsonDecoder = null)
    {
        @trigger_error("Class JsonMachine is deprecated. Use class Items instead.", E_USER_DEPRECATED);

        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
        $this->jsonDecoder = $jsonDecoder;

        $this->parser = new Parser(
            new Lexer($this->bytesIterator,true),
            $this->jsonPointer,
            $this->jsonDecoder ?: new ExtJsonDecoder(true)
        );
    }

    /**
     * @param string $string
     * @param string $jsonPointer
     * @param Decoder|ItemDecoder $jsonDecoder
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromString($string, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new StringChunks($string), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param string $file
     * @param string $jsonPointer
     * @param Decoder|ItemDecoder $jsonDecoder
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new FileChunks($file), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param resource $stream
     * @param string $jsonPointer
     * @param Decoder|ItemDecoder $jsonDecoder
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static(new StreamChunks($stream), $jsonPointer, $jsonDecoder);
    }

    /**
     * @param iterable $iterable
     * @param string $jsonPointer
     * @param Decoder|ItemDecoder $jsonDecoder
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromIterable($iterable, $jsonPointer = '', $jsonDecoder = null)
    {
        return new static($iterable, $jsonPointer, $jsonDecoder);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->parser;
    }

    public function getPosition()
    {
        return $this->parser->getPosition();
    }
}
