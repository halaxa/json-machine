<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;

class FileChunks implements \IteratorAggregate
{
    /** @var string */
    private $fileName;

    /** @var int */
    private $chunkSize;

    /**
     * @param string $fileName
     * @param int $chunkSize
     */
    public function __construct($fileName, $chunkSize = 1024 * 8)
    {
        $this->fileName = $fileName;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @return \Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $fileHandle = fopen($this->fileName, 'r');
        try {
            $streamChunks = new StreamChunks($fileHandle, $this->chunkSize);
            foreach ($streamChunks as $chunk) {
                // todo 'yield from $streamChunks' on php 7
                yield $chunk;
            }
        } finally {
            fclose($fileHandle);
        }
    }
}
