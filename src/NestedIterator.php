<?php

declare(strict_types=1);

namespace JsonMachine;

use Iterator;
use JsonMachine\Exception\JsonMachineException;

class NestedIterator implements \RecursiveIterator
{
    /** @var Iterator */
    private $iterator;

    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->iterator->current();
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        return $this->iterator->next();
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->iterator->key();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->iterator->valid();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->iterator->rewind();
    }

    #[\ReturnTypeWillChange]
    public function hasChildren()
    {
        return $this->iterator->current() instanceof Iterator;
    }

    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return $this->hasChildren() ? new self($this->current()) : null;
    }

    public function advanceToKey($key)
    {
        $iterator = $this->iterator;

        while ($key !== $iterator->key() && $iterator->valid()) {
            $iterator->next();
        }

        if ($key !== $iterator->key()) {
            throw new JsonMachineException("Key '$key' was not found.");
        }

        return $iterator->current();
    }

    public function toArray()
    {
    }
}
