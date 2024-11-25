<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * @implements GeneratorAggregate<int, string>
 */
class FileChunks implements GeneratorAggregate
{
    /** @var string */
    private $fileName;

    /** @var int */
    private $chunkSize;

    public function __construct(string $fileName, int $chunkSize = 1024 * 8)
    {
        $this->fileName = $fileName;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @throws Exception\InvalidArgumentException
     */
    public function getIterator(): \Generator
    {
        $fileHandle = fopen($this->fileName, 'r');
        try {
            yield from new StreamChunks($fileHandle, $this->chunkSize);
        } finally {
            fclose($fileHandle);
        }
    }
}
