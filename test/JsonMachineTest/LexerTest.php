<?php

namespace JsonMachineTest;

use JsonMachine\Lexer;
use JsonMachine\Exception;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratesTokens()
    {
        $data = 'data://text/plain,{}[],:null,"string" false:true,1,100000,1.555{-56]"","\\""';
        $expected = ['{','}','[',']',',',':','null',',','"string"','false',':','true',',','1',',','100000',',','1.555','{','-56',']','""',',','"\\""'];
        $this->assertEquals($expected, iterator_to_array(new Lexer(fopen($data, 'r'))));
    }

    public function testThrowsIfNoResource()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class);
        new Lexer(false);
    }
}
