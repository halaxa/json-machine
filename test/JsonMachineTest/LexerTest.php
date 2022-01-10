<?php

namespace JsonMachineTest;

use JsonMachine\DebugLexer;
use JsonMachine\Lexer;
use JsonMachine\Exception;
use JsonMachine\StreamChunks;
use JsonMachine\StringChunks;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    public function bothDebugModes()
    {
        return [
            'debug enabled' => [DebugLexer::class],
            'debug disabled' => [Lexer::class],
        ];
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyYieldsZeroToken($lexerClass)
    {
        $data = ['0'];
        $expected = ['0'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($data))));

        $stream = fopen('data://text/plain,{"value":0}', 'r');
        $expected = ['{', '"value"', ':', '0', '}'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new StreamChunks($stream, 10))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testGeneratesTokens($lexerClass)
    {
        $data = ['{}[],:null,"string" false:', 'true,1,100000,1.555{-56]"","\\""'];
        $expected = ['{','}','[',']',',',':','null',',','"string"','false',':','true',',','1',',','100000',',','1.555','{','-56',']','""',',','"\\""'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($data))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testWithBOM($lexerClass)
    {
        $data = ["\xEF\xBB\xBF" . '{}'];
        $expected = ['{','}'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($data))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesTwoBackslashesAtTheEndOfAString($lexerClass)
    {
        $this->assertEquals(['"test\\\\"', ':'], iterator_to_array(new $lexerClass(new \ArrayIterator(['"test\\\\":']))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesEscapedQuotesInTheMiddleOfAString($lexerClass)
    {
        $json = '"test\"test":';
        $expected = ['"test\"test"', ':'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator([$json]))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitBeforeStringEnd($lexerClass)
    {
        $chunks = ['{"path": {"key":"value', '"}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value"', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitBeforeEscapedCharacter($lexerClass)
    {
        $chunks = ['{"path": {"key":"value\\', '""}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value\""', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitAfterEscapedCharacter($lexerClass)
    {
        $chunks = ['{"path": {"key":"value\\"', '"}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value\""', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $lexerClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testAnyPossibleChunkSplit($lexerClass)
    {
        $json = '
          {
            "datafeed": {
              "info": {
                "category": "Category name"
              },
              "programs": [
                {
                  "program_info": {
                    "id": "X0\"\\\\",
                    "number": 123,
                    "constant": false
                  }
                },
                {
                  "program_info": {
                    "id": "\b\f\n\r\t\u0020X1"
                  }
                }
              ]
            }
          }
        ';

        $expected = [
            '{', '"datafeed"', ':', '{', '"info"', ':', '{', '"category"', ':', '"Category name"', '}', ',',
            '"programs"', ':', '[', '{', '"program_info"', ':', '{', '"id"', ':', '"X0\\"\\\\"', ',', '"number"', ':',
            '123', ',', '"constant"', ':', 'false', '}', '}', ',', '{', '"program_info"', ':', '{', '"id"', ':',
            '"\b\f\n\r\t\u0020X1"', '}', '}', ']', '}', '}'
        ];

        foreach (range(1, strlen($json)) as $chunkLength) {
            $chunks = str_split($json, $chunkLength);
            $result = iterator_to_array(new $lexerClass($chunks));

            $this->assertSame($expected, $result);
        }
    }

    /**
     * @param string $formattedJsonFilePath
     * @dataProvider dataProvidesLocationalData
     */
    public function testProvidesLocationalDataWhenDebugEnabled($formattedJsonFilePath)
    {
        $json = file_get_contents($formattedJsonFilePath);
        $lexer = new DebugLexer(new StringChunks($json));
        $expectedTokens = $this->expectedTokens();
        $i = 0;

        foreach ($lexer as $token) {
            $i++;
            $expectedToken = array_shift($expectedTokens);

            $this->assertEquals($expectedToken[0], $token, 'token failed with expected token #' . $i);
            $this->assertEquals($expectedToken[1], $lexer->getLine(), 'line failed with expected token #' . $i);
            $this->assertEquals($expectedToken[2], $lexer->getColumn(), 'column failed with expected token #' . $i);
        }
    }

    /**
     * @param string $formattedJsonFilePath
     * @dataProvider dataProvidesLocationalData
     */
    public function testProvidesLocationalDataWhenDebugDisabled($formattedJsonFilePath)
    {
        $json = file_get_contents($formattedJsonFilePath);
        $lexer = new Lexer(new StringChunks($json));
        $expectedTokens = $this->expectedTokens();
        $i = 0;

        foreach ($lexer as $token) {
            $i++;
            $expectedToken = array_shift($expectedTokens);

            $this->assertEquals($expectedToken[0], $token, 'token failed with expected token #' . $i);
            $this->assertEquals(1, $lexer->getLine(), 'line failed with expected token #' . $i);
            $this->assertEquals(0, $lexer->getColumn(), 'column failed with expected token #' . $i);
        }
    }

    public function dataProvidesLocationalData()
    {
        return [
            'cr new lines' => [__DIR__ . '/formatted-cr.json'],
            'lf new lines' => [__DIR__ . '/formatted-lf.json'],
            'crlf new lines' => [__DIR__ . '/formatted-crlf.json'],
        ];
    }

    private function expectedTokens()
    {
        return [
            // lexeme, line, column
            ['{', 1, 1],
            ['"id"', 2, 3],
            [':', 2, 7],
            ['54640519019642880', 2, 9],
            [',', 2, 26],
            ['"user"', 3, 3],
            [':', 3, 9],
            ['{', 3, 11],
            ['"notifications"', 4, 5],
            [':', 4, 20],
            ['null', 4, 22],
            [',', 4, 26],
            ['"profile_use_background_image"', 5, 5],
            [':', 5, 35],
            ['true', 5, 37],
            [',', 5, 41],
            ['"listed_count"', 6, 5],
            [':', 6, 19],
            ['6', 6, 21],
            [',', 6, 22],
            ['"created_at"', 7, 5],
            [':', 7, 17],
            ['"Thu Mar 24 19:45:44 +0000 2011"', 7, 19],
            [',', 7, 51],
            ['"profile_link_color"', 8, 5],
            [':', 8, 25],
            ['"0084B4"', 8, 27],
            [',', 8, 35],
            ['"show_all_inline_media"', 9, 5],
            [':', 9, 28],
            ['false', 9, 30],
            [',', 9, 35],
            ['"follow_request_sent"', 10, 5],
            [':', 10, 26],
            ['null', 10, 28],
            [',', 10, 32],
            ['"geo_enabled"', 11, 5],
            [':', 11, 18],
            ['false', 11, 20],
            [',', 11, 25],
            ['"profile_sidebar_border_color"', 12, 5],
            [':', 12, 35],
            ['"C0DEED"', 12, 37],
            [',', 12, 45],
            ['"url"', 13, 5],
            [':', 13, 10],
            ['null', 13, 12],
            [',', 13, 16],
            ['"id"', 14, 5],
            [':', 14, 9],
            ['271572434', 14, 11],
            [',', 14, 20],
            ['"contributors_enabled"', 15, 5],
            [':', 15, 27],
            ['false', 15, 29],
            [',', 15, 34],
            ['"utc_offset"', 16, 5],
            [':', 16, 17],
            ['null', 16, 19],
            ['}', 17, 3],
            [',', 17, 4],
            ['"geo"', 18, 3],
            [':', 18, 8],
            ['null', 18, 10],
            ['}', 19, 1]
        ];
    }
}
