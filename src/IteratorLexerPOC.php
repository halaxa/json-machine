<?php

declare(strict_types=1);

namespace JsonMachine;

use Iterator;

class IteratorLexerPOC implements \Iterator
{
    /** @var Iterator */
    private $jsonChunks;

    /** @var array */
    private $tokenBoundaries = [];

    /** @var array */
    private $jsonInsignificantBytes = [];

    /** @var string */
    private $carryToken;

    /** @var string */
    private $current = '';

    /** @var int */
    private $key = -1;

    /** @var string */
    private $chunk;

    /** @var int */
    private $chunkLength;

    /** @var int */
    private $chunkIndex;

    /** @var bool */
    private $inString = false;

    /** @var string */
    private $tokenBuffer = '';

    /** @var bool */
    private $escaping = false;

    /**
     * @param Iterator<string> $jsonChunks
     */
    public function __construct(Iterator $jsonChunks)
    {
        $this->jsonChunks = $jsonChunks;
        $this->tokenBoundaries = $this->mapOfBoundaryBytes();
        $this->jsonInsignificantBytes = $this->jsonInsignificantBytes();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->jsonChunksRewind();
        $this->next();
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->current = '';

        for (; $this->chunkIndex < $this->chunkLength; ++$this->chunkIndex) {
            if ($this->carryToken != null) {
                $this->current = $this->carryToken;
                $this->carryToken = null;
                ++$this->key;

                return;
            }

            $byte = $this->chunk[$this->chunkIndex];

            if ($this->escaping) {
                $this->escaping = false;
                $this->tokenBuffer .= $byte;
                continue;
            }

            if ($this->jsonInsignificantBytes[$byte]) {
                $this->tokenBuffer .= $byte;
                continue;
            }

            if ($this->inString) {
                if ($byte == '"') {
                    $this->inString = false;
                } elseif ($byte == '\\') {
                    $this->escaping = true;
                }
                $this->tokenBuffer .= $byte;
                continue;
            }

            if (isset($this->tokenBoundaries[$byte])) { // if byte is any token boundary
                if ($this->tokenBuffer != '') {
                    $this->current = $this->tokenBuffer;
                    $this->tokenBuffer = '';
                }
                if ($this->tokenBoundaries[$byte]) { // if byte is not whitespace token boundary
                    $this->carryToken = $byte;
                }
                if ($this->current != '') {
                    ++$this->key;
                    ++$this->chunkIndex;

                    return;
                }
            } else {
                if ($byte == '"') {
                    $this->inString = true;
                }
                $this->tokenBuffer .= $byte;
            }
        }

        if ($this->jsonChunksNext()) {
            $this->next();
        } elseif ($this->carryToken) {
            $this->current = $this->carryToken;
            $this->carryToken = null;
            ++$this->key;
        }
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->current !== '';
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    private function mapOfBoundaryBytes(): array
    {
        $utf8bom = "\xEF\xBB\xBF";

        $boundary = [];
        $boundary[$utf8bom[0]] = 0;
        $boundary[$utf8bom[1]] = 0;
        $boundary[$utf8bom[2]] = 0;
        $boundary[' '] = 0;
        $boundary["\n"] = 0;
        $boundary["\r"] = 0;
        $boundary["\t"] = 0;

        $boundary['{'] = 1;
        $boundary['}'] = 1;
        $boundary['['] = 1;
        $boundary[']'] = 1;
        $boundary[':'] = 1;
        $boundary[','] = 1;

        return $boundary;
    }

    private function jsonInsignificantBytes(): array
    {
        $bytes = [];
        foreach (range(0, 255) as $ord) {
            $bytes[chr($ord)] = ! in_array(
                chr($ord),
                ['\\', '"', "\xEF", "\xBB", "\xBF", ' ', "\n", "\r", "\t", '{', '}', '[', ']', ':', ',']
            );
        }

        return $bytes;
    }

    private function initCurrentChunk(): bool
    {
        $valid = $this->jsonChunks->valid();

        if ($valid) {
            $this->chunk = $this->jsonChunks->current();
            $this->chunkLength = strlen($this->chunk);
            $this->chunkIndex = 0;
        }

        return $valid;
    }

    public function getPosition(): int
    {
        return 0;
    }

    public function getLine(): int
    {
        return 1;
    }

    public function getColumn(): int
    {
        return 0;
    }

    private function jsonChunksRewind(): bool
    {
        $this->jsonChunks->rewind();

        return $this->initCurrentChunk();
    }

    private function jsonChunksNext(): bool
    {
        $this->jsonChunks->next();

        return $this->initCurrentChunk();
    }
}
