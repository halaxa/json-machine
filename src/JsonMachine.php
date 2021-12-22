<?php

namespace JsonMachine;

use JsonMachine\JsonDecoder\Decoder;

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
     * @var Decoder|null
     */
    private $jsonDecoder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var bool
     */
    private $debugEnabled;

    /**
     * @param iterable $bytesIterator
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @param bool $debugEnabled
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($bytesIterator, $jsonPointer = '', $jsonDecoder = null, $debugEnabled = true)
    {
        @trigger_error("Class JsonMachine is deprecated. Use class Items instead.", E_USER_DEPRECATED);

        $this->bytesIterator = $bytesIterator;
        $this->jsonPointer = $jsonPointer;
        $this->jsonDecoder = $jsonDecoder;
        $this->debugEnabled = $debugEnabled;

        $this->parser = new Parser(
            new Lexer(
                $this->bytesIterator,
                $this->debugEnabled
            ),
            $this->jsonPointer,
            $this->jsonDecoder
        );
    }

    /**
     * @param string $string
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @param bool $debugEnabled
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromString($string, $jsonPointer = '', $jsonDecoder = null, $debugEnabled = true)
    {
        return new static(new StringChunks($string), $jsonPointer, $jsonDecoder, $debugEnabled);
    }

    /**
     * @param string $file
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @param bool $debugEnabled
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, $jsonPointer = '', $jsonDecoder = null, $debugEnabled = true)
    {
        return new static(new FileChunks($file), $jsonPointer, $jsonDecoder, $debugEnabled);
    }

    /**
     * @param resource $stream
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @param bool $debugEnabled
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, $jsonPointer = '', $jsonDecoder = null, $debugEnabled = true)
    {
        return new static(new StreamChunks($stream), $jsonPointer, $jsonDecoder, $debugEnabled);
    }

    /**
     * @param iterable $iterable
     * @param string $jsonPointer
     * @param Decoder $jsonDecoder
     * @param bool $debugEnabled
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public static function fromIterable($iterable, $jsonPointer = '', $jsonDecoder = null, $debugEnabled = true)
    {
        return new static($iterable, $jsonPointer, $jsonDecoder, $debugEnabled);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->parser->getIterator();
    }

    public function getPosition()
    {
        return $this->parser->getPosition();
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->debugEnabled;
    }
}
