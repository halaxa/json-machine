<?php

namespace JsonMachine;

class Token
{
    const SCALAR_CONST = 1;
    const SCALAR_STRING = 2;
    const OBJECT_START = 4;
    const OBJECT_END = 8;
    const ARRAY_START = 16;
    const ARRAY_END = 32;
    const COMMA = 64;
    const COLON = 128;

    /** @var integer */
    private $line;

    /** @var integer */
    private $column;

    /** @var integer */
    private $type;

    /** @var string */
    private $value;

    /**
     * @param integer $line The line number of the lexeme's position. If there are no linebreaks this will be "line 1".
     * @param integer $column The start position of the lexeme within the line.
     * @param integer $type One of the class constants.
     * @param string $value The lexeme of the type, or just an empty value if type is enough.
     */
    public function __construct($line, $column, $type, $value = '')
    {
        $this->setLine($line);
        $this->setColumn($column);
        $this->setType($type);
        $this->setValue($value);
    }

    /** @var integer Index starts at one. */
    public function getLine()
    {
        return $this->line;
    }

    /** @var integer Index starts at zero. */
    public function getColumn()
    {
        return $this->column;
    }

    /** @var integer */
    public function getType()
    {
        return $this->type;
    }

    /** @var string */
    public function getValue()
    {
        return $this->value;
    }

    private function setLine($line)
    {
        if (!is_integer($line)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s" for line parameter. Please provide an integer.', gettype($line)));
        }

        $this->line = $line;
    }

    private function setColumn($column)
    {
        if (!is_integer($column)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s" for column parameter. Please provide an integer.', gettype($column)));
        }

        $this->column = $column;
    }

    private function setType($type)
    {
        if (!is_integer($type)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s" for type parameter. Please use one of the class constants.', gettype($type)));
        }

        $this->type = $type;
    }

    private function setValue($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s" for value parameter. Please provide a string.', gettype($value)));
        }

        $this->value = $value;
    }
}
