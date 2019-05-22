<?php

namespace JsonMachine;

use JsonMachine\Token;

class Lexer implements \IteratorAggregate
{
    /** @var resource */
    private $bytesIterator;

    /** @var integer */
    private $position;

    /** @var integer */
    private $line;

    /** @var integer */
    private $column;

    /**
     * @param \Traversable $bytesIterator
     */
    public function __construct(\Traversable $bytesIterator)
    {
        $this->bytesIterator = $bytesIterator;
        $this->position = 0;
        $this->line = 0;
        $this->column = 0;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->bytesIterator as $buffer) {
            for ($i = 0, $l = strlen($buffer); $i < $l; $i++) {
                $character = $buffer[$i];

                switch ($character) {
                    case " ":
                    case "\t":
                        break;

                    case "\r":
                    case "\n":
                        // advance to next line and reset column
                        $this->line++;
                        $this->column = -1;
                        break;

                    case '{':
                        yield new Token($this->line, $this->column, Token::OBJECT_START, '{');
                        break;

                    case '}':
                        yield new Token($this->line, $this->column, Token::OBJECT_END, '}');
                        break;

                    case '[':
                        yield new Token($this->line, $this->column, Token::ARRAY_START, '[');
                        break;

                    case ']':
                        yield new Token($this->line, $this->column, Token::ARRAY_END, ']');
                        break;

                    case ':':
                        yield new Token($this->line, $this->column, Token::COLON, ':');
                        break;

                    case ',':
                        yield new Token($this->line, $this->column, Token::COMMA, ',');
                        break;

                    case '"':
                        $value = $character;
                        $width = 0;
                        $escaping = false;

                        while (true) {
                            $char = $buffer[++$i];
                            $value .= $char;
                            $width++;

                            if (($char === '"' && !$escaping) || $char === '') {
                                break;
                            }

                            $escaping = ($char === '\\' && !$escaping); // deals with escaped back slashes
                        }

                        yield new Token($this->line, $this->column, Token::SCALAR_STRING, $value);

                        $this->column += $width;

                        break;

                    default:
                        $value = $character;
                        $width = 0;

                        while (true) {
                            $char = isset($buffer[++$i]) ? $buffer[$i] : '';

                            if (in_array($char, ['{', '}', '[', ']', ':', ',', '"', " ", "\t", "\r", "\n", ''])) {
                                $i--;  // let the outer loop handle these characters

                                break;
                            }

                            $value .= $char;
                            $width++;
                        }

                        yield new Token($this->line, $this->column, Token::SCALAR_CONST, $value);

                        $this->column += $width;
                }

                $this->column++;
            }
        }

        $this->position = $this->column;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
}
