<?php

namespace JsonMachine;

use Iterator;

class ExtTokens implements \Iterator
{

    /**
     * @var Iterator
     */
    private $jsonChunks;

    /**
     * @var array
     */
    private $tokenBoundaries = [];

    /**
     * @var array
     */
    private $jsonInsignificantBytes = [];

    /**
     * @var string
     */
    private $carryToken = '';

    /**
     * @var string
     */
    private $current = '';

    /**
     * @var int
     */
    private $key = -1;

    /**
     * @var string
     */
    private $chunk;

    /**
     * @var int
     */
    private $chunkLength;

    /**
     * @var int
     */
    private $chunkIndex;

    /**
     * @var bool
     */
    private $inString = false;

    /**
     * @var string
     */
    private $tokenBuffer = '';

    /**
     * @var bool
     */
    private $escaping = false;

    /**
     * @param Iterator<string> $jsonChunks
     * @param \Iterator $jsonChunks
     */
    public function __construct(\Iterator $jsonChunks)
    {
    }

    public function rewind()
    {
    }

    public function next()
    {
    }

    public function valid()
    {
    }

    public function current()
    {
    }

    public function key()
    {
    }

    /**
     * @return array
     */
    private function mapOfBoundaryBytes(): array
    {
    }

    /**
     * @return array
     */
    private function jsonInsignificantBytes(): array
    {
    }

    /**
     * @return bool
     */
    private function initCurrentChunk(): bool
    {
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
    }

    /**
     * @return int
     */
    public function getColumn(): int
    {
    }

    /**
     * @return bool
     */
    private function jsonChunksRewind(): bool
    {
    }

    /**
     * @return bool
     */
    private function jsonChunksNext(): bool
    {
    }
}
