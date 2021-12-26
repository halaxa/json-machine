<?php

namespace JsonMachine;

class Lexer implements \IteratorAggregate, PositionAware
{
    /** @var iterable */
    private $bytesIterator;
    private $debug = false;

    private $position = 0;
    private $line = 1;
    private $column = 0;

    /**
     * @param iterable $byteChunks
     * @param bool $debug
     */
    public function __construct($byteChunks, $debug = false)
    {
        $this->bytesIterator = $byteChunks;
        $this->debug = $debug;
    }

    /**
     * @return \Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        if ($this->debug) {
            return $this->debugParse();
        } else {
            return $this->parse();
        }
    }

    public function parse()
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

        foreach ($this->bytesIterator as $bytes) {
            $bytesLength = strlen($bytes);
            for ($i = 0; $i < $bytesLength; ++$i) {
                $byte = $bytes[$i];
                if ($inString) {
                    if ($byte == '"' && !$escaping) {
                        $inString = false;
                    }
                    $escaping = ($byte == '\\' && !$escaping);
                    $tokenBuffer .= $byte;
                    continue;
                }

                if (isset($$byte)) { // is token boundary
                    if ($tokenBuffer != '') {
                        yield $tokenBuffer;
                        $tokenBuffer = '';
                    }
                    if ($$byte) { // is not whitespace token boundary
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

    public function debugParse()
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
        $position = 1;
        $line = 1;
        $column = 0;

        foreach ($this->bytesIterator as $bytes) {
            $bytesLength = strlen($bytes);
            for ($i = 0; $i < $bytesLength; ++$i) {
                $byte = $bytes[$i];
                if ($inString) {
                    if ($byte == '"' && !$escaping) {
                        $inString = false;
                    }
                    $escaping = ($byte == '\\' && !$escaping);
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
                        $this->position = $position + $i;
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
        if ($tokenBuffer != '') {
            $this->position = $position;
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
     * @return integer The line number of the lexeme currently being processed (index starts at one).
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return integer The, currently being processed, lexeme's position within the line (index starts at one).
     */
    public function getColumn()
    {
        return $this->column;
    }
}
