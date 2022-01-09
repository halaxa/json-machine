<?php declare(strict_types=1);


namespace JsonMachine;

use Exception;
use Traversable;

class QueueChunks implements \IteratorAggregate
{
    private $queue = [];

    public function push(string $jsonChunk)
    {
        $this->queue[] = $jsonChunk;
    }

    public function getIterator()
    {
        yield from $this->queue;
        $this->queue = [];
    }
}
