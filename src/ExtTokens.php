<?php

namespace JsonMachine;


class ExtTokens implements \IteratorAggregate
{
    /** @var iterable */
    private $jsonChunks;

    /**
     * @param iterable<string> $jsonChunks
     */
    public function __construct($jsonChunks)
    {
        $this->jsonChunks = $jsonChunks;
    }

    public function getIterator()
    {
        $lastIndex = 0;
        $inString = false;
        $escaping = false;
        $tokenBuffer = "";

        foreach ($this->jsonChunks as $jsonChunk) {
            while (NULL !== ($token = jsonmachine_next_token($jsonChunk, $tokenBuffer, $escaping, $inString, $lastIndex))) {
                yield $token;
            }
        }
    }
}
