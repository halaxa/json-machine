<?php

declare(strict_types=1);

namespace JsonMachine;

use Iterator;
use IteratorAggregate;
use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use LogicException;

/**
 * Entry-point facade for recursive iteration.
 */
final class RecursiveItems implements \RecursiveIterator, PositionAware
{
    use FacadeTrait;

    /** @var IteratorAggregate */
    private $parser;

    /** @var ItemsOptions */
    private $options;

    /** @var Iterator */
    private $parserIterator;

    public function __construct(IteratorAggregate $parser, ?ItemsOptions $options = null)
    {
        if ( ! $options) {
            $options = new ItemsOptions();
        }

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
        if ($current instanceof IteratorAggregate) {
            return new self($current, $this->options);
        } elseif ( ! is_scalar($current)) {
            throw new JsonMachineException(
                sprintf(
                    '%s only accepts scalar or IteratorAggregate values. %s given.',
                    self::class,
                    is_object($current) ? get_class($current) : gettype($current)
                )
            );
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

    /**
     * Finds the desired key on this level and returns its value.
     * It moves the internal cursor to it so subsequent calls to self::current() returns the same value.
     *
     * @param $key
     * @return mixed
     * @throws JsonMachineException When the key is not found on this level.
     */
    public function advanceToKey($key)
    {
        if ( ! $this->parserIterator) {
            $this->rewind();
        }
        $iterator = $this->parserIterator;

        while ($key !== $iterator->key() && $iterator->valid()) {
            $iterator->next();
        }

        if ($key !== $iterator->key()) {
            throw new JsonMachineException("Key '$key' was not found.");
        }

        return $iterator->current();
    }

    /**
     * Recursively materializes this iterator level to array.
     * Moves its internal pointer to the end.
     *
     * @return array
     */
    public function toArray(): array
    {
        return self::toArrayRecursive($this);
    }

    private static function toArrayRecursive(\Traversable $traversable): array
    {
        $array = [];
        foreach ($traversable as $key => $value) {
            if ($value instanceof \Traversable) {
                $value = self::toArrayRecursive($value);
            }
            $array[$key] = $value;
        }

        return $array;
    }
}
