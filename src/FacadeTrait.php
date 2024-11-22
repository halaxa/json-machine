<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use LogicException;

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
     * @throws InvalidArgumentException
     */
    private static function createParser(iterable $bytesIterator, ItemsOptions $options, bool $recursive): Parser
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
     * Returns JSON bytes read so far.
     */
    public function getPosition()
    {
        if ($this->parser instanceof PositionAware) {
            return $this->parser->getPosition();
        }

        throw new LogicException('getPosition() may only be called on PositionAware');
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
