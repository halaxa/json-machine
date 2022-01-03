<?php

namespace JsonMachine;

use Generator;

class Lexer implements \IteratorAggregate, PositionAware
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

    /**
     * @return Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        // init ASCII byte map as variable variables for the fastest lookup
        foreach (range(0,255) as $ord) {
            ${chr($ord)} = ! in_array(
                chr($ord),
                ["\\", '"', "\xEF", "\xBB", "\xBF", ' ', "\n", "\r", "\t", '{', '}', '[', ']', ':', ',']
            );
        }

        $boundary = $this->mapOfBoundaryBytes();

        $inString = false;
        $tokenBuffer = '';
        $escaping = false;

        foreach ($this->jsonChunks as $jsonChunk) {
            $bytesLength = strlen($jsonChunk);
            for ($i = 0; $i < $bytesLength; ++$i) {
                $byte = $jsonChunk[$i];
                if ($escaping) {
                    $escaping = false;
                    $tokenBuffer .= $byte;
                    continue;
                }

                if ($$byte) { // is non-significant byte
                    $tokenBuffer .= $byte;
                    continue;
                }

                if ($inString) {
                    if ($byte == '"') {
                        $inString = false;
                    } elseif ($byte == '\\') {
                        $escaping = true;
                    }
                    $tokenBuffer .= $byte;
                    continue;
                }

                if (isset($boundary[$byte])) { // if byte is any token boundary
                    if ($tokenBuffer != '') {
                        yield $tokenBuffer;
                        $tokenBuffer = '';
                    }
                    if ($boundary[$byte]) { // if byte is not whitespace token boundary
                        yield $byte;
                    }
                } else {
                    if ($byte == '"') {
                        $inString = true;
                    }
                    $tokenBuffer .= $byte;
                }
            }
        }
        if ($tokenBuffer != '') {
            yield $tokenBuffer;
        }
    }

    private function mapOfBoundaryBytes(): array
    {
        $utf8bom1 = "\xEF";
        $utf8bom2 = "\xBB";
        $utf8bom3 = "\xBF";

        $boundary = [];
        $boundary[$utf8bom1] = 0;
        $boundary[$utf8bom2] = 0;
        $boundary[$utf8bom3] = 0;
        $boundary[' ']       = 0;
        $boundary["\n"]      = 0;
        $boundary["\r"]      = 0;
        $boundary["\t"]      = 0;
        $boundary['{']       = 1;
        $boundary['}']       = 1;
        $boundary['[']       = 1;
        $boundary[']']       = 1;
        $boundary[':']       = 1;
        $boundary[',']       = 1;

        return $boundary;
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
