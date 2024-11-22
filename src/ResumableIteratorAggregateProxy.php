<?php

declare(strict_types=1);

namespace JsonMachine;

use InvalidArgumentException;
use IteratorAggregate;
use LogicException;

/**
 * Allows to resume iteration of the inner IteratorAggregate via foreach, which would be otherwise impossible as
 * foreach implicitly calls reset(). This Iterator does not pass the reset() call to the inner Iterator thus enabling
 * to follow up on a previous iteation.
 */
class ResumableIteratorAggregateProxy implements IteratorAggregate, PositionAware
{
    /** @var IteratorAggregate */
    private $iteratorAggregate;

    public function __construct(\Traversable $iteratorAggregate)
    {
        // todo remove when the whole system moves to GeneratorAggregate
        if ( ! $iteratorAggregate instanceof IteratorAggregate) {
            throw new InvalidArgumentException('$iteratorAggregate must be an instance of IteratorAggregate');
        }

        $this->iteratorAggregate = $iteratorAggregate;
    }

    public function getIterator(): \Traversable
    {
        $iterator = toIterator($this->iteratorAggregate->getIterator());
        while ($iterator->valid()) {
            yield $iterator->key() => $iterator->current();
            $iterator->next();
        }
    }

    public function __call($name, $arguments)
    {
        return $this->iteratorAggregate->$name(...$arguments);
    }

    /**
     * Returns JSON bytes read so far.
     */
    public function getPosition()
    {
        if ($this->iteratorAggregate instanceof PositionAware) {
            return $this->iteratorAggregate->getPosition();
        }

        throw new LogicException('getPosition() may only be called on PositionAware');
    }
}
