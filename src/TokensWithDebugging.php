<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * @implements \IteratorAggregate<int, string>
 */
class TokensWithDebugging implements \IteratorAggregate, PositionAware
{
    /** @var iterable<int, string> */
    private $jsonChunks;

    /** @var int */
    private $position = 0;

    /** @var int */
    private $line = 1;

    /** @var int */
    private $column = 0;

    /**
     * @param iterable<int, string> $jsonChunks
     */
    public function __construct($jsonChunks)
    {
        $this->jsonChunks = $jsonChunks;
    }

    /**
     * @return \Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        // Treat UTF-8 BOM bytes as whitespace
        ${"\xEF"} = ${"\xBB"} = ${"\xBF"} = 0;

        ${' '} = 0;
        ${"\n"} = 0;
        ${"\r"} = 0;
        ${"\t"} = 0;
        ${'{'} = 1;
        ${'}'} = 1;
        ${'['} = 1;
        ${']'} = 1;
        ${':'} = 1;
        ${','} = 1;

        $inString = false;
        $tokenBuffer = '';
        $escaping = false;
        $tokenWidth = 0;
        $ignoreLF = false;
        $position = 0;
        $line = 1;
        $column = 0;

        foreach ($this->jsonChunks as $bytes) {
            $bytesLength = strlen($bytes);
            for ($i = 0; $i < $bytesLength; ++$i) {
                /** @var string $byte */
                $byte = $bytes[$i];
                if ($inString) {
                    if ($byte == '"' && ! $escaping) {
                        $inString = false;
                    }
                    $escaping = ($byte == '\\' && ! $escaping);
                    $tokenBuffer .= $byte;
                    ++$tokenWidth;
                    continue;
                }

                if (isset($$byte)) {
                    ++$column;
                    if ($tokenBuffer != '') {
                        $this->position = $position + $i;
                        $this->column = $column;
                        $this->line = $line;
                        yield $tokenBuffer;
                        $column += $tokenWidth;
                        $tokenBuffer = '';
                        $tokenWidth = 0;
                    }
                    if ($$byte) { // is not whitespace
                        $this->position = $position + $i + 1;
                        $this->column = $column;
                        $this->line = $line;
                        yield $byte;
                    // track line number and reset column for each newline
                    } elseif ($byte == "\n") {
                        // handle CRLF newlines
                        if ($ignoreLF) {
                            --$column;
                            $ignoreLF = false;
                            continue;
                        }
                        ++$line;
                        $column = 0;
                    } elseif ($byte == "\r") {
                        ++$line;
                        $ignoreLF = true;
                        $column = 0;
                    }
                } else {
                    if ($byte == '"') {
                        $inString = true;
                    }
                    $tokenBuffer .= $byte;
                    ++$tokenWidth;
                }
            }
            $position += $i;
        }
        $this->position = $position;

        if ($tokenBuffer != '') {
            $this->column = $column;
            yield $tokenBuffer;
        }
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Returns the line number of the lexeme currently being processed (index starts at one).
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * The position of currently being processed lexeme within the line (index starts at one).
     *
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }
}
