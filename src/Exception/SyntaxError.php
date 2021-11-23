<?php

namespace JsonMachine\Exception;

class SyntaxError extends JsonMachineException
{
    public function __construct($message, $position)
    {
        parent::__construct($message." At position $position.");
    }
}
