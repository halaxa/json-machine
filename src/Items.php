<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;

/**
 * Entry-point facade for JSON Machine.
 */
final class Items implements \IteratorAggregate, PositionAware
{
    /**
     * @var iterable
     */
    private $chunks;

    /**
     * @var string
     */
    private $jsonPointer;

    /**
     * @var ItemDecoder|null
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
     *
     * @throws InvalidArgumentException
     */
    public function __construct($bytesIterator, array $options = [])
    {
        $options = new ItemsOptions($options);

        $this->chunks = $bytesIterator;
        $this->jsonPointer = $options['pointer'];
        $this->jsonDecoder = $options['decoder'];
        $this->debugEnabled = $options['debug'];

        if ($this->debugEnabled) {
            $tokensClass = TokensWithDebugging::class;
        } else {
            $tokensClass = Tokens::class;
        }

        $this->parser = new Parser(
            new $tokensClass(
                $this->chunks
            ),
            $this->jsonPointer,
            $this->jsonDecoder ?: new ExtJsonDecoder()
        );
    }

    /**
     * @param string $string
     *
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function fromString($string, array $options = [])
    {
        return new self(new StringChunks($string), $options);
    }

    /**
     * @param string $file
     *
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, array $options = [])
    {
        return new self(new FileChunks($file), $options);
    }

    /**
     * @param resource $stream
     *
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, array $options = [])
    {
        return new self(new StreamChunks($stream), $options);
    }

    /**
     * @param iterable $iterable
     *
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromIterable($iterable, array $options = [])
    {
        return new self($iterable, $options);
    }

    /**
     * @return \Generator
     *
     * @throws Exception\PathNotFoundException
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->parser->getIterator();
    }

    /**
     * @throws Exception\JsonMachineException
     */
    public function getPosition()
    {
        return $this->parser->getPosition();
    }

    public function getJsonPointers(): array
    {
        return $this->parser->getJsonPointers();
    }

    /**
     * @throws Exception\JsonMachineException
     */
    public function getCurrentJsonPointer(): string
    {
        return $this->parser->getCurrentJsonPointer();
    }

    /**
     * @throws Exception\JsonMachineException
     */
    public function getMatchedJsonPointer(): string
    {
        return $this->parser->getMatchedJsonPointer();
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->debugEnabled;
    }
}
