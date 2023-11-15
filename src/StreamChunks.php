<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;

/**
 * @implements \IteratorAggregate<int, string>
 */
class StreamChunks implements \IteratorAggregate
{
    /** @var resource */
    private $stream;

    /** @var int */
    private $chunkSize;

    /**
     * @param resource $stream
     * @param int      $chunkSize
     *
     * @throws InvalidArgumentException
     */
    public function __construct($stream, $chunkSize = 1024 * 8)
    {
        if ( ! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Argument $stream must be a valid stream resource.');
        }
        $this->stream = $stream;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @return \Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        while ('' !== ($chunk = fread($this->stream, $this->chunkSize))) {
            yield $chunk;
        }
    }
}
