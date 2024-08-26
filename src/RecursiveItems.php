<?php

declare(strict_types=1);

namespace JsonMachine;

use Iterator;
use JsonMachine\Exception\InvalidArgumentException;

/**
 * Entry-point facade for recursive iteration.
 */
final class RecursiveItems implements \RecursiveIterator, PositionAware
{
    use FacadeTrait;

    /** @var Parser */
    private $parser;

    /** @var ItemsOptions */
    private $options;

    /** @var Iterator */
    private $parserIterator;

    public function __construct(Parser $parser, ItemsOptions $options)
    {
        $this->parser = $parser;
        $this->options = $options;
        $this->debugEnabled = $options['debug'];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromString($string, array $options = []): self
    {
        $options = new ItemsOptions($options);
        return new self(
            self::createParser(new StringChunks($string), $options, true),
            $options
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromFile($file, array $options = []): self
    {
        $options = new ItemsOptions($options);
        return new self(
            self::createParser(new FileChunks($file), $options, true),
            $options
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromStream($stream, array $options = []): self
    {
        $options = new ItemsOptions($options);
        return new self(
            self::createParser(new StreamChunks($stream), $options, true),
            $options
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromIterable($iterable, array $options = []): self
    {
        $options = new ItemsOptions($options);
        return new self(
            self::createParser($iterable, $options, true),
            $options
        );
    }

    public function current()
    {
        $current = $this->parserIterator->current();
        if ($current instanceof Parser) {
            return new self($current, $this->options);
        }

        return $current;
    }

    public function next()
    {
        $this->parserIterator->next();
    }

    public function key()
    {
        return $this->parserIterator->key();
    }

    public function valid(): bool
    {
        return $this->parserIterator->valid();
    }

    public function rewind()
    {
        $this->parserIterator = $this->parser->getIterator();
        $this->parserIterator->rewind();
    }

    public function hasChildren(): bool
    {
        return $this->current() instanceof self;
    }

    public function getChildren()
    {
        $current = $this->current();
        if ($current instanceof self) {
            return $current;
        }

        return null;
    }
}
