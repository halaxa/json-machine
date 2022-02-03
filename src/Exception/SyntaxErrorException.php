<?php

namespace JsonMachine\Exception;

class SyntaxErrorException extends JsonMachineException
{
    public function __construct($message, $position)
    {
        parent::__construct($message." At position $position.");
    }
}
