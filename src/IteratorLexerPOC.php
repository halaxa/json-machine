<?php

namespace JsonMachine;

use Iterator;

class IteratorLexerPOC implements \Iterator
{
    /** @var Iterator */
    private $jsonChunks;


    /** @var array */
    private $tokenBoundaries = [];

    /** @var array  */
    private $jsonInsignificantBytes = [];


    /** @var array */
    private $tokenQueue = [];

    /** @var string */
    private $currentToken = '';

    /** @var int */
    private $currentTokenKey = -1;

    /** @var string */
    private $chunk = '';

    /** @var int */
    private $chunkLength = 0;

    /** @var int */
    private $chunkIndex = 0;


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


    public function rewind()
    {
        $this->advanceToNextJsonChunk(true);
        $this->next();
    }


    public function next()
    {
        $this->currentToken = '';
//var_dump([$this->tokenQueue, $this->chunkIndex, $this->chunkLength, $this->chunk]);

        if ($this->chunkIndex == $this->chunkLength) {
            $this->advanceToNextJsonChunk(false);
        }

        if ($this->tokenQueue) {
            $this->currentToken = array_shift($this->tokenQueue);
            ++$this->currentTokenKey;
            return;
        }

        for ( ; $this->chunkIndex < $this->chunkLength; ++$this->chunkIndex)
        {
            $byte = $this->chunk[$this->chunkIndex];
//var_dump($byte);
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
                $this->flushTokenBuffer();
                if ($this->tokenBoundaries[$byte]) { // if byte is not whitespace token boundary
                    $this->tokenQueue[] = $byte;
                }
                if ($this->tokenQueue) {
                    $this->currentToken = array_shift($this->tokenQueue);
                    ++$this->currentTokenKey;
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

        if ( ! $this->advanceToNextJsonChunk(false)) {
            $this->flushTokenBuffer();
        } else {
            $this->next();
        }
    }


    public function valid()
    {
        return $this->currentToken !== '';
    }


    public function current()
    {
        return $this->currentToken;
    }


    public function key()
    {
        return $this->currentTokenKey;
    }


    private function mapOfBoundaryBytes(): array
    {
        $utf8bom = "\xEF\xBB\xBF";

        $boundary = [];
        $boundary[$utf8bom[0]] = 0;
        $boundary[$utf8bom[1]] = 0;
        $boundary[$utf8bom[2]] = 0;
        $boundary[' ']         = 0;
        $boundary["\n"]        = 0;
        $boundary["\r"]        = 0;
        $boundary["\t"]        = 0;

        $boundary['{']         = 1;
        $boundary['}']         = 1;
        $boundary['[']         = 1;
        $boundary[']']         = 1;
        $boundary[':']         = 1;
        $boundary[',']         = 1;

        return $boundary;
    }


    private function jsonInsignificantBytes(): array
    {
        $allBytes = [];
        foreach (range(0, 255) as $ord) {
            $allBytes[chr($ord)] = !in_array(
                chr($ord),
                ["\\", '"', "\xEF", "\xBB", "\xBF", ' ', "\n", "\r", "\t", '{', '}', '[', ']', ':', ',']
            );
        }

        return $allBytes;
    }


    private function advanceToNextJsonChunk(bool $rewind): bool
    {
        if ($rewind) {
            $this->jsonChunks->rewind();
        } else {
            $this->jsonChunks->next();
        }
        $valid = $this->jsonChunks->valid();

        if ($valid) {
            $this->chunk = $this->jsonChunks->current();
            $this->chunkLength = strlen($this->chunk);
            $this->chunkIndex = 0;
//            $this->jsonChunks->key();
        }

        return $valid;
    }


    private function flushTokenBuffer()
    {
        if ($this->tokenBuffer != '') {
            $this->tokenQueue[] = $this->tokenBuffer;
            $this->tokenBuffer = '';
        }
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
}
