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
        $lexer = new Lexer(new \ArrayIterator($data));

        $this->assertEquals($expected, $this->flattenToLexemes($lexer));
    }

    public function testCorrectlyParsesTwoBackslashesAtTheEndOfAString()
    {
        $lexer = new Lexer(new \ArrayIterator(['"test\\\\":']));

        $this->assertEquals(['"test\\\\"', ':'], $this->flattenToLexemes($lexer));
    }

    private function flattenToLexemes(Lexer $lexer)
    {
        $tokens = iterator_to_array($lexer);

        return array_map(function ($token) {
            return $token->getValue();
        }, $tokens);
    }
}
