<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;

trait FacadeTrait
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var bool
     */
    private $debugEnabled;

    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * @param iterable $bytesIterator
     *
     * @throws InvalidArgumentException
     */
    private static function createParser($bytesIterator, ItemsOptions $options, bool $recursive): Parser
    {
        if ($options['debug']) {
            $tokensClass = TokensWithDebugging::class;
        } else {
            $tokensClass = Tokens::class;
        }

        return new Parser(
            new $tokensClass(
                $bytesIterator
            ),
            $options['pointer'],
            $options['decoder'] ?: new ExtJsonDecoder(),
            $recursive
        );
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
     * @param string $string
     */
    abstract public static function fromString($string, array $options = []): self;

    /**
     * @param string $file
     */
    abstract public static function fromFile($file, array $options = []): self;

    /**
     * @param resource $stream
     */
    abstract public static function fromStream($stream, array $options = []): self;

    /**
     * @param iterable $iterable
     */
    abstract public static function fromIterable($iterable, array $options = []): self;

}
