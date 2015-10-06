<?php

namespace JsonIterator;

class Lexer implements \IteratorAggregate
{
    /** @var resource */
    private $stream;

    private $position = 0;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if ( ! is_resource($stream)) {
            throw new Exception\InvalidArgumentException('Parameter $stream must be valid resource.');
        }
        $this->stream = $stream;
    }
    
    /**
     * @return \Generator
     */
    public function getIterator()
    {
        $inString = false;
        $tokenBuffer = '';
        $previousByte = null;

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

        while ('' !== ($bytes = fread($this->stream, 1024 * 8))) {
            $bytesLength = strlen($bytes);
            for ($i = 0; $i < $bytesLength; ++$i) {
                $byte = $bytes[$i];
                ++$this->position;

                if ($inString) {
                    if ($byte === '"' && $previousByte !== '\\') {
                        $inString = false;
                    } else {
                        $previousByte = $byte;
                    }
                    $tokenBuffer .= $byte;
                    continue;
                }

                if (isset($$byte)) {
                    if ($tokenBuffer !== '') {
                        yield $tokenBuffer;
                        $tokenBuffer = '';
                    }
                    if ($$byte) { // is not whitespace
                        yield $byte;
                    }
                } else {
                    if ($byte === '"') {
                        $inString = true;
                    }
                    $tokenBuffer .= $byte;
                }
            }
        }
        if ($tokenBuffer !== '') {
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
}
