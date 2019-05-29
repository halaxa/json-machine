<?php

namespace JsonMachineTest;

use JsonMachine\Lexer;
use JsonMachine\Exception;
use JsonMachine\StringBytes;

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

    public function testProvidesLocationalData()
    {
        $json = file_get_contents(__DIR__ . "/formatted.json");
        $lexer = new Lexer(new StringBytes($json));
        $tokens = $this->tokensWithLocationalInformation();
        $i = 0;

        foreach ($lexer as $lexeme) {
            $i++;
            $token = array_shift($tokens);

            $this->assertEquals($token[0], $lexeme, 'lexeme failed with data set #' . $i);
            $this->assertEquals($token[1], $lexer->getPosition(), 'position failed with data set #' . $i);
            $this->assertEquals($token[2], $lexer->getLine(), 'line failed with data set #' . $i);
            $this->assertEquals($token[3], $lexer->getColumn(), 'column failed with data set #' . $i);
        }
    }

    private function tokensWithLocationalInformation()
    {
        return [
            // lexeme, position, line, column
            ['{', 1, 1, 1],
            ['"id"', 9, 2, 3],
            [':', 9, 2, 7],
            ['54640519019642880', 28, 2, 9],
            [',', 28, 2, 26],
            ['"user"', 38, 3, 3],
            [':', 38, 3, 9],
            ['{', 40, 3, 11],
            ['"notifications"', 61, 4, 5],
            [':', 61, 4, 20],
            ['null', 67, 4, 22],
            [',', 67, 4, 26],
            ['"profile_use_background_image"', 103, 5, 5],
            [':', 103, 5, 35],
            ['true', 109, 5, 37],
            [',', 109, 5, 41],
            ['"listed_count"', 129, 6, 5],
            [':', 129, 6, 19],
            ['6', 132, 6, 21],
            [',', 132, 6, 22],
            ['"created_at"', 150, 7, 5],
            [':', 150, 7, 17],
            ['"Thu Mar 24 19:45:44 +0000 2011"', 184, 7, 19],
            [',', 184, 7, 51],
            ['"profile_link_color"', 210, 8, 5],
            [':', 210, 8, 25],
            ['"0084B4"', 220, 8, 27],
            [',', 220, 8, 35],
            ['"show_all_inline_media"', 249, 9, 5],
            [':', 249, 9, 28],
            ['false', 256, 9, 30],
            [',', 256, 9, 35],
            ['"follow_request_sent"', 283, 10, 5],
            [':', 283, 10, 26],
            ['null', 289, 10, 28],
            [',', 289, 10, 32],
            ['"geo_enabled"', 308, 11, 5],
            [':', 308, 11, 18],
            ['false', 315, 11, 20],
            [',', 315, 11, 25],
            ['"profile_sidebar_border_color"', 351, 12, 5],
            [':', 351, 12, 35],
            ['"C0DEED"', 361, 12, 37],
            [',', 361, 12, 45],
            ['"url"', 372, 13, 5],
            [':', 372, 13, 10],
            ['null', 378, 13, 12],
            [',', 378, 13, 16],
            ['"id"', 388, 14, 5],
            [':', 388, 14, 9],
            ['271572434', 399, 14, 11],
            [',', 399, 14, 20],
            ['"contributors_enabled"', 427, 15, 5],
            [':', 427, 15, 27],
            ['false', 434, 15, 29],
            [',', 434, 15, 34],
            ['"utc_offset"', 452, 16, 5],
            [':', 452, 16, 17],
            ['null', 458, 16, 19],
            ['}', 461, 17, 3],
            [',', 462, 17, 4],
            ['"geo"', 471, 18, 3],
            [':', 471, 18, 8],
            ['null', 477, 18, 10],
            ['}', 478, 19, 1]
        ];
    }
}
