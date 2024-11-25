<?php

declare(strict_types=1);

namespace JsonMachine;

use Generator;
use JsonMachine\Exception\InvalidArgumentException;

/**
 * Entry-point facade for JSON Machine.
 */
final class Items implements \IteratorAggregate, PositionAware
{
    use FacadeTrait;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(GeneratorAggregate $bytesIterator, array $options = [])
    {
        $options = new ItemsOptions($options);
        $this->debugEnabled = $options['debug'];

        $this->parser = $this->createParser($bytesIterator, $options, false);
    }

    /**
     * @param string $string
     *
     * @throws InvalidArgumentException
     */
    public static function fromString($string, array $options = []): self
    {
        return new self(new StringChunks($string), $options);
    }

    /**
     * @param string $file
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromFile($file, array $options = []): self
    {
        return new self(new FileChunks($file), $options);
    }

    /**
     * @param resource $stream
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromStream($stream, array $options = []): self
    {
        return new self(new StreamChunks($stream), $options);
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public static function fromIterable(iterable $iterable, array $options = []): self
    {
        return new self(new GeneratorAggregateWrapper($iterable), $options);
    }

    public function getIterator(): Generator
    {
        return $this->parser->getIterator();
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
}
