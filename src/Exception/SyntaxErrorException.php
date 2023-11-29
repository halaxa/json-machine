<?php

declare(strict_types=1);

namespace JsonMachine\Exception;

class SyntaxErrorException extends JsonMachineException
{
    public function __construct(string $message, int $position)
    {
        parent::__construct($message." At position $position.");
    }
}
