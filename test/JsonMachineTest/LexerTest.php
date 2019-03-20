<?php

namespace JsonMachineTest;

use JsonMachine\Lexer;
use JsonMachine\Exception;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratesTokens()
    {
        $data = ['{}[],:null,"string" false:', 'true,1,100000,1.555{-56]"","\\""'];
        $expected = ['{','}','[',']',',',':','null',',','"string"','false',':','true',',','1',',','100000',',','1.555','{','-56',']','""',',','"\\""'];
        $this->assertEquals($expected, iterator_to_array(new Lexer(new \ArrayIterator($data))));
    }

    public function testCorrectlyParsesTwoBackslashesAtTheEndOfAString()
    {
        $this->assertEquals(['"test\\\\"', ':'], iterator_to_array(new Lexer(new \ArrayIterator(['"test\\\\":']))));
    }
}
