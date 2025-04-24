<?php

declare(strict_types=1);

namespace JsonMachine;

use Exception;
use Generator;
use Iterator;
use IteratorAggregate;
use JsonMachine\Exception\BadMethodCallException;
use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\OutOfBoundsException;

/**
 * Entry-point facade for recursive iteration.
 */
final class RecursiveItems implements \RecursiveIterator, PositionAware, \ArrayAccess
{
    use FacadeTrait;

    /** @var IteratorAggregate */
    private $parser;

    /** @var ItemsOptions */
    private $options;

    /** @var Generator|Iterator */
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

    /**
     * @return mixed Move to return type when PHP 7 support is dropped
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->parserIterator->current();
        if ($current instanceof IteratorAggregate) {
            return new self($current, $this->options);
        } elseif ( ! is_scalar($current) && ! ($current === null)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s only accepts scalar or IteratorAggregate values. %s given.',
                    self::class,
                    is_object($current) ? get_class($current) : gettype($current)
                )
            );
        }

        return $current;
    }

    public function next(): void
    {
        $this->parserIterator->next();
    }

    /**
     * @return mixed Move to return type when PHP 7 support is dropped
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->parserIterator->key();
    }

    public function valid(): bool
    {
        return $this->parserIterator->valid();
    }

    public function rewind(): void
    {
        $this->parserIterator = toIterator($this->parser->getIterator());
        $this->parserIterator->rewind();
    }

    public function hasChildren(): bool
    {
        return $this->current() instanceof self;
    }

    public function getChildren(): ?\RecursiveIterator
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
     * @param string|int $key
     *
     * @return scalar|self
     *
     * @throws OutOfBoundsException when the key is not found on this level
     */
    public function advanceToKey($key)
    {
        if ( ! $this->parserIterator) {
            $this->rewind();
        }
        $iterator = $this;

        while ($key !== $iterator->key() && $iterator->valid()) {
            $iterator->next();
        }

        if ($key !== $iterator->key()) {
            throw new OutOfBoundsException("Key '$key' was not found.");
        }

        return $iterator->current();
    }

    /**
     * Recursively materializes this iterator level to array.
     * Moves its internal pointer to the end.
     *
     * @throws JsonMachineException
     */
    public function toArray(): array
    {
        try {
            /** @throws Exception */
            $this->rewind();
        } catch (Exception $e) {
            if (false !== strpos($e->getMessage(), 'generator')) {
                throw new JsonMachineException(
                    'Method toArray() can only be called before any items in the collection have been accessed.'
                );
            }
        }

        return self::toArrayRecursive($this);
    }

    private static function toArrayRecursive(self $traversable): array
    {
        $array = [];
        foreach ($traversable as $key => $value) {
            if ($value instanceof self) {
                $value = self::toArrayRecursive($value);
            }
            $array[$key] = $value;
        }

        return $array;
    }

    public function offsetExists($offset): bool
    {
        try {
            $this->advanceToKey($offset);

            return true;
        } catch (JsonMachineException $e) {
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->advanceToKey($offset);
    }

    /**
     * @param $offset
     * @param $value
     *
     * @throws BadMethodCallException
     *
     * @deprecated
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Unsupported: Cannot set a value on a JSON stream');
    }

    /**
     * @param $offset
     *
     * @throws BadMethodCallException
     *
     * @deprecated
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Unsupported: Cannot unset a value from a JSON stream');
    }
}
