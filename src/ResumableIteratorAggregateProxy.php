<?php

declare(strict_types=1);

namespace JsonMachine;

use IteratorAggregate;

/**
 * Allows to resume iteration of the inner IteratorAggregate via foreach, which would be otherwise impossible as
 * foreach implicitly calls reset(). This Iterator does not pass the reset() call to the inner Iterator thus enabling
 * to follow up on a previous iteation.
 */
class ResumableIteratorAggregateProxy implements IteratorAggregate
{
    /** @var IteratorAggregate */
    private $iteratorAggregate;

    public function __construct(IteratorAggregate $iteratorAggregate)
    {
        $this->iteratorAggregate = $iteratorAggregate;
    }

    public function getIterator(): \Traversable
    {
        $iterator = $this->iteratorAggregate->getIterator();
        while ($iterator->valid()) {
            yield $iterator->key() => $iterator->current();
            $iterator->next();
        }
    }

    public function __call($name, $arguments)
    {
        return $this->iteratorAggregate->$name(...$arguments);
    }
}
