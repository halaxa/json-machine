<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\FileChunks;
use JsonMachine\StreamChunks;
use JsonMachine\StringChunks;
use JsonMachine\Tokens;
use JsonMachine\TokensWithDebugging;

/**
 * @covers \JsonMachine\Tokens
 * @covers \JsonMachine\TokensWithDebugging
 */
class TokensTest extends \PHPUnit_Framework_TestCase
{
    public function bothDebugModes()
    {
        return [
            'debug enabled' => [TokensWithDebugging::class],
            'debug disabled' => [Tokens::class],
        ];
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyYieldsZeroToken($tokensClass)
    {
        $data = ['0'];
        $expected = ['0'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($data))));

        $stream = fopen('data://text/plain,{"value":0}', 'r');
        $expected = ['{', '"value"', ':', '0', '}'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new StreamChunks($stream, 10))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testGeneratesTokens($tokensClass)
    {
        $data = ['{}[],:null,"string" false:', 'true,1,100000,1.555{-56]"","\\""'];
        $expected = ['{', '}', '[', ']', ',', ':', 'null', ',', '"string"', 'false', ':', 'true', ',', '1', ',', '100000', ',', '1.555', '{', '-56', ']', '""', ',', '"\\""'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($data))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testWithBOM($tokensClass)
    {
        $data = ["\xEF\xBB\xBF".'{}'];
        $expected = ['{', '}'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($data))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesTwoBackslashesAtTheEndOfAString($tokensClass)
    {
        $this->assertEquals(['"test\\\\"', ':'], iterator_to_array(new $tokensClass(new \ArrayIterator(['"test\\\\":']))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesEscapedQuotesInTheMiddleOfAString($tokensClass)
    {
        $json = '"test\"test":';
        $expected = ['"test\"test"', ':'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator([$json]))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitBeforeStringEnd($tokensClass)
    {
        $chunks = ['{"path": {"key":"value', '"}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value"', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitBeforeEscapedCharacter($tokensClass)
    {
        $chunks = ['{"path": {"key":"value\\', '""}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value\""', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testCorrectlyParsesChunksSplitAfterEscapedCharacter($tokensClass)
    {
        $chunks = ['{"path": {"key":"value\\"', '"}}'];
        $expected = ['{', '"path"', ':', '{', '"key"', ':', '"value\""', '}', '}'];
        $this->assertEquals($expected, iterator_to_array(new $tokensClass(new \ArrayIterator($chunks))));
    }

    /**
     * @dataProvider bothDebugModes
     */
    public function testAnyPossibleChunkSplit($tokensClass)
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
            '"\b\f\n\r\t\u0020X1"', '}', '}', ']', '}', '}',
        ];

        foreach (range(1, strlen($json)) as $chunkLength) {
            $chunks = str_split($json, $chunkLength);
            $result = iterator_to_array(new $tokensClass($chunks));

            $this->assertSame($expected, $result);
        }
    }

    /**
     * @dataProvider jsonFilesWithDifferentLineEndings
     */
    public function testProvidesLocationalDataWhenDebugEnabled(string $jsonFilePath)
    {
        $jsonFileContents = file_get_contents($jsonFilePath);
        $tokens = new TokensWithDebugging(new StringChunks($jsonFileContents));
        $expectedTokens = $this->expectedTokens();
        $i = 0;

        foreach ($tokens as $token) {
            ++$i;
            $expectedToken = array_shift($expectedTokens);

            $this->assertEquals($expectedToken[0], $token, 'token failed with expected token #'.$i);
            $this->assertEquals($expectedToken[1], $tokens->getLine(), 'line failed with expected token #'.$i);
            $this->assertEquals($expectedToken[2], $tokens->getColumn(), 'column failed with expected token #'.$i);
        }
    }

    /**
     * @dataProvider jsonFilesWithDifferentLineEndings
     */
    public function testProvidesLocationalDataWhenDebugDisabled(string $jsonFilePath)
    {
        $tokens = new Tokens(new FileChunks($jsonFilePath));
        $expectedTokens = $this->expectedTokens();
        $i = 0;

        foreach ($tokens as $token) {
            ++$i;
            $expectedToken = array_shift($expectedTokens);

            $this->assertEquals($expectedToken[0], $token, 'token failed with expected token #'.$i);
            $this->assertEquals(1, $tokens->getLine(), 'line failed with expected token #'.$i);
            $this->assertEquals(0, $tokens->getColumn(), 'column failed with expected token #'.$i);
        }
    }

    public function testGetPositionWthDebugging()
    {
        $tokens = new TokensWithDebugging(['[   1, "two", false ]']);
        $expectedPosition = [1, 5, 6, 12, 13, 19, 21];

        $this->assertSame(0, $tokens->getPosition());
        foreach ($tokens as $index => $item) {
            $this->assertSame($expectedPosition[$index], $tokens->getPosition(), "index:$index, item:$item");
        }
        $this->assertSame(21, $tokens->getPosition());
    }

    public function testGetPositionNoDebugging()
    {
        $tokens = new Tokens(['[   1, "two", false ]']);

        $this->assertSame(0, $tokens->getPosition());
        foreach ($tokens as $index => $item) {
            $this->assertSame(0, $tokens->getPosition(), "index:$index, item:$item");
        }
        $this->assertSame(0, $tokens->getPosition());
    }

    public function jsonFilesWithDifferentLineEndings()
    {
        return [
            'cr new lines' => [__DIR__.'/formatted-cr.json'],
            'lf new lines' => [__DIR__.'/formatted-lf.json'],
            'crlf new lines' => [__DIR__.'/formatted-crlf.json'],
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
            ['}', 5, 3],
            [',', 5, 4],
            ['"geo"', 6, 3],
            [':', 6, 8],
            ['"test"', 6, 10],
            ['}', 7, 1],
        ];
    }
}
