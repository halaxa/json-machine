<?php

declare(strict_types=1);

function toIterator(Traversable $traversable): Iterator
{
    if ($traversable instanceof IteratorAggregate) {
        return toIterator($traversable->getIterator());
    }

    if ($traversable instanceof Iterator) {
        return $traversable;
    }

    throw new \LogicException('Cannot turn Traversable into Iterator');
}
