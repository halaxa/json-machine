<?php

namespace JsonMachine;

class Lexer implements \IteratorAggregate, PositionAware
{
    /** @var iterable */
    private $jsonChunks;
    private $debug = false;

    private $position = 0;
    private $line = 1;
    private $column = 0;

    /**
     * @param iterable<string> $jsonChunks
     * @param bool $debug
     */
    public function __construct($jsonChunks, $debug = false)
    {
        $this->jsonChunks = $jsonChunks;
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

    private function mapOfBoundaryBytes()
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

        foreach ($this->jsonChunks as $bytes) {
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
