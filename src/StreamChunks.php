<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;

/**
 * @implements GeneratorAggregate<int, string>
 */
class StreamChunks implements GeneratorAggregate
{
    /** @var resource */
    private $stream;

    /** @var int */
    private $chunkSize;

    /**
     * @param resource $stream
     *
     * @throws InvalidArgumentException
     */
    public function __construct($stream, int $chunkSize = 1024 * 8)
    {
        if ( ! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Argument $stream must be a valid stream resource.');
        }
        $this->stream = $stream;
        $this->chunkSize = $chunkSize;
    }

    public function getIterator(): \Generator
    {
        while ('' !== ($chunk = fread($this->stream, $this->chunkSize))) {
            yield $chunk;
        }
    }
}
