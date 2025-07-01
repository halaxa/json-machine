<?php

declare(strict_types=1);

namespace JsonMachine;

use Generator;

class Tokens implements \IteratorAggregate, PositionAware
{
    /** @var iterable */
    private $jsonChunks;

    /** @var Generator */
    private $generator;

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
        if ( ! $this->generator) {
            $this->generator = $this->createGenerator();
        }

        return $this->generator;
    }

    private function createGenerator(): Generator
    {
        $regex = '/ [{}\[\],:] | [^\xEF\xBB\xBF\s{}\[\],:]+ /x';

        $inString = 0;
        $carry = '';

        foreach ($this->jsonChunks as $jsonChunk) {
            $chunkBlocks = explode('"', $carry.$jsonChunk);
            $carry = '';

            $chunkItemsLastSafeIndex = count($chunkBlocks) - 2;
            for ($i = 0; $i <= $chunkItemsLastSafeIndex; ++$i) {
                if ($inString) {
                    if ($this->stringIsEscaping($chunkBlocks[$i])) {
                        $carry .= $chunkBlocks[$i].'"';
                    } else {
                        yield "\"$carry$chunkBlocks[$i]\"";
                        $carry = '';
                        $inString = 0;
                    }
                } else {
                    $chunkBlock = trim($chunkBlocks[$i]);
                    if (strlen($chunkBlock) == 1) {
                        yield $chunkBlock;
                    } else {
                        preg_match_all($regex, $chunkBlock, $matches);
                        yield from $matches[0];
                    }
                    $inString = 1;
                }
            }

            if ($inString) {
                $carry .= $chunkBlocks[$i];
            } else {
                preg_match_all($regex, $chunkBlocks[$i], $matches);
                $carry = array_pop($matches[0]);
                yield from $matches[0];
            }
        }

        if ($carry !== null && $carry !== '') {
            yield ($inString ? '"' : '').$carry;
        }
    }

    private function stringIsEscaping(string $token): bool
    {
        $i = strlen($token);
        $slashes = 0;
        while (--$i >= 0 && $token[$i] === '\\') {
            ++$slashes;
        }

        return $slashes % 2 != 0;
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
