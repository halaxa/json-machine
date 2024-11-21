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
        $insignificantBytes = $this->insignificantBytes();
        $tokenBoundaries = $this->tokenBoundaries();
        $colonCommaBracket = $this->colonCommaBracketTokenBoundaries();

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

                if (isset($insignificantBytes[$byte])) { // is a JSON-structure insignificant byte
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

                if (isset($tokenBoundaries[$byte])) {
                    if ($tokenBuffer != '') {
                        yield $tokenBuffer;
                        $tokenBuffer = '';
                    }
                    if (isset($colonCommaBracket[$byte])) {
                        yield $byte;
                    }
                } else { // else branch matches `"` but also `\` outside of a string literal which is an error anyway but strictly speaking not correctly parsed token
                    $inString = true;
                    $tokenBuffer .= $byte;
                }
            }
        }
        if ($tokenBuffer != '') {
            yield $tokenBuffer;
        }
    }

    private function tokenBoundaries()
    {
        $utf8bom1 = "\xEF";
        $utf8bom2 = "\xBB";
        $utf8bom3 = "\xBF";

        return array_merge(
            [
                $utf8bom1 => true,
                $utf8bom2 => true,
                $utf8bom3 => true,
                ' ' => true,
                "\n" => true,
                "\r" => true,
                "\t" => true,
            ],
            $this->colonCommaBracketTokenBoundaries()
        );
    }

    private function colonCommaBracketTokenBoundaries(): array
    {
        return [
            '{' => true,
            '}' => true,
            '[' => true,
            ']' => true,
            ':' => true,
            ',' => true,
        ];
    }

    private function insignificantBytes(): array
    {
        $insignificantBytes = [];
        foreach (range(0, 255) as $ord) {
            if ( ! in_array(
                chr($ord),
                ['\\', '"', "\xEF", "\xBB", "\xBF", ' ', "\n", "\r", "\t", '{', '}', '[', ']', ':', ',']
            )) {
                $insignificantBytes[chr($ord)] = true;
            }
        }

        return $insignificantBytes;
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
