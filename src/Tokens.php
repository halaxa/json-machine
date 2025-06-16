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
        $regex = '/
            [{}\[\],:]
            #| true|false|null
            #| [\deE.+-]+
            #| (t|tr|tru|f|fa|fal|fals|n|nu|nul)$
            | [^\xEF\xBB\xBF\s{}\[\],:]+    # todo make matching logic positive as in comments above and solve 2 failing tests
        /x';

        $inString = 0;
        $carry = '';

        foreach ($this->jsonChunks as $jsonChunk) {
            $chunkItems = explode('"', $carry.$jsonChunk);
            $carry = '';

            $chunkItemsLastSafeIndex = count($chunkItems) - 2;
            for ($i = 0; $i <= $chunkItemsLastSafeIndex; ++$i) {
                if ($inString) {
                    if ($this->stringIsEscaping($chunkItems[$i])) {
                        $carry .= $chunkItems[$i].'"';
                    } else {
                        yield '"'.$carry.$chunkItems[$i].'"';
                        $carry = '';
                        $inString = 0;
                    }
                } else {
                    preg_match_all($regex, $chunkItems[$i], $matches);
                    yield from $matches[0];
                    $inString = 1;
                }
            }

            if ($inString) {
                $carry .= $chunkItems[$i];
            } else {
                preg_match_all($regex, $chunkItems[$i], $matches);
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
