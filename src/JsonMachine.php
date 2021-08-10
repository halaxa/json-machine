<?php

namespace JsonMachine;

use JsonMachine\JsonDecoder\Decoder;

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
     * @var Decoder|null
     */
    private $jsonDecoder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var bool|null
     */
    private $storeUnusedJson;

    /**
     * @param iterable $bytesIterator
     * @param string $jsonPointer
     * @param Decoder|null $jsonDecoder
     * @param bool|null $storeUnusedJson
     */
    public function __construct($bytesIterator, $jsonPointer = '', $jsonDecoder = null, $storeUnusedJson = null)
    {
        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
        $this->jsonDecoder = $jsonDecoder;
        $this->storeUnusedJson = $storeUnusedJson;

        $this->parser = new Parser(new Lexer($this->bytesIterator), $this->jsonPointer, $this->jsonDecoder, $storeUnusedJson);
    }

    /**
     * @param string $string
     * @param string $jsonPointer
     * @param Decoder|null $jsonDecoder
     * @param bool|null $storeUnusedJson
     * @return self
     */
    public static function fromString($string, $jsonPointer = '', $jsonDecoder = null, $storeUnusedJson = null)
    {
        return new static(new StringChunks($string), $jsonPointer, $jsonDecoder, $storeUnusedJson);
    }

    /**
     * @param string $file
     * @param string $jsonPointer
     * @param Decoder|null $jsonDecoder
     * @param bool|null $storeUnusedJson
     * @return self
     */
    public static function fromFile($file, $jsonPointer = '', $jsonDecoder = null, $storeUnusedJson = null)
    {
        return new static(new FileChunks($file), $jsonPointer, $jsonDecoder, $storeUnusedJson);
    }

    /**
     * @param resource $stream
     * @param string $jsonPointer
     * @param Decoder|null $jsonDecoder
     * @param bool|null $storeUnusedJson
     * @return self
     */
    public static function fromStream($stream, $jsonPointer = '', $jsonDecoder = null, $storeUnusedJson = null)
    {
        return new static(new StreamChunks($stream), $jsonPointer, $jsonDecoder, $storeUnusedJson);
    }

    /**
     * @param \Traversable|array $iterable
     * @param string $jsonPointer
     * @param Decoder|null $jsonDecoder
     * @param bool|null $storeUnusedJson
     * @return self
     */
    public static function fromIterable($iterable, $jsonPointer = '', $jsonDecoder = null, $storeUnusedJson = null)
    {
        return new static($iterable, $jsonPointer, $jsonDecoder, $storeUnusedJson);
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

    public function getUnusedJson()
    {
        return $this->parser->getUnusedJson();
    }
}
