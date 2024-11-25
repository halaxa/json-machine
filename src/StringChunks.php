<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * @implements GeneratorAggregate<int, string>
 */
class StringChunks implements GeneratorAggregate
{
    /** @var string */
    private $string;

    /** @var int */
    private $chunkSize;

    public function __construct(string $string, int $chunkSize = 1024 * 8)
    {
        $this->string = $string;
        $this->chunkSize = $chunkSize;
    }

    public function getIterator(): \Generator
    {
        $len = strlen($this->string);
        for ($i = 0; $i < $len; $i += $this->chunkSize) {
            yield substr($this->string, $i, $this->chunkSize);
        }
    }
}
