<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\Lexer;
use JsonMachine\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataSyntax
     * @param string $jsonPointer
     * @param string $json
     * @param array $expectedResult
     */
    public function testSyntax($jsonPointer, $json, $expectedResult)
    {
        $result = [];
        foreach ($this->createParser($json, $jsonPointer) as $key => $value) {
            $result[] = [$key => $value];
        }

        $this->assertSame($expectedResult, $result);
    }

    public function dataSyntax()
    {
        return [
            ['', '{}', []],
            ['', '{"a": "b"}', [['a'=>'b']]],
            ['', '{"a":{"b":{"c":1}}}', [['a'=>['b'=>['c'=>1]]]]],
            ['', '[]', []],
            ['', '[null,true,false,"a",0,1,42.5]', [[0=>null],[1=>true],[2=>false],[3=>"a"],[4=>0],[5=>1],[6=>42.5]]],
            ['', '[{"c":1}]', [[['c'=>1]]]],
            ['', '[{"c":1},"string",{"d":2},false]', [[0=>['c'=>1]],[1=>"string"],[2=>['d'=>2]],[3=>false]]],
            ['', '[false,{"c":1},"string",{"d":2}]', [[0=>false],[1=>['c'=>1]],[2=>"string"],[3=>['d'=>2]]]],
            ['', '[{"c":1,"d":2}]', [[['c'=>1, 'd'=>2]]]],
            ['/', '{"":{"c":1,"d":2}}', [['c'=>1],['d'=>2]]],
            ['/~0', '{"~":{"c":1,"d":2}}', [['c'=>1],['d'=>2]]],
            ['/~1', '{"/":{"c":1,"d":2}}', [['c'=>1],['d'=>2]]],
            ['/path', '{"path":{"c":1,"d":2}}', [['c'=>1],['d'=>2]]],
            ['/path', '{"no":[null], "path":{"c":1,"d":2}}', [['c'=>1],['d'=>2]]],
            ['/0', '[{"c":1,"d":2}, [null]]', [['c'=>1],['d'=>2]]],
            ['/0/path', '[{"path":{"c":1,"d":2}}]', [['c'=>1],['d'=>2]]],
            ['/1/path', '[[null], {"path":{"c":1,"d":2}}]', [['c'=>1],['d'=>2]]],
            ['/path/0', '{"path":[{"c":1,"d":2}, [null]]}', [['c'=>1],['d'=>2]]],
            ['/path/1', '{"path":[null,{"c":1,"d":2}, [null]]}', [['c'=>1],['d'=>2]]],
            ['/path/to', '{"path":{"to":{"c":1,"d":2}}}', [['c'=>1],['d'=>2]]],
            ['/path/after-vector', '{"path":{"array":[],"after-vector":{"c":1,"d":2}}}', [['c'=>1],['d'=>2]]],
            ['/path/after-vector', '{"path":{"array":["item"],"after-vector":{"c":1,"d":2}}}', [['c'=>1],['d'=>2]]],
            ['/path/after-vector', '{"path":{"object":{"item":null},"after-vector":{"c":1,"d":2}}}', [['c'=>1],['d'=>2]]],
            ['/path/after-vectors', '{"path":{"array":[],"object":{},"after-vectors":{"c":1,"d":2}}}', [['c'=>1],['d'=>2]]],
            ['/0/0', '[{"0":{"c":1,"d":2}}]', [['c'=>1],['d'=>2]]],
            ['/1/1', '[0,{"1":{"c":1,"d":2}}]', [['c'=>1],['d'=>2]]],
            'PR-19-FIX' => ['/datafeed/programs/1', file_get_contents(__DIR__.'/PR-19-FIX.json'), [['program_info'=>['id'=>'X1']]]],
            'ISSUE-41-FIX' => ['/path', '{"path":[{"empty":{}},{"value":1}]}', [[["empty"=>[]]],[1=>["value"=>1]]]],
            ['/-', '[{"one": 1,"two": 2},{"three": 3,"four": 4}]', [['one'=>1], ['two'=>2], ['three'=>3], ['four'=>4]]],
            ['/zero/-', '{"zero":[{"one": 1,"two": 2},{"three": 3,"four": 4}]}', [['one'=>1], ['two'=>2], ['three'=>3], ['four'=>4]]],
            ['/zero/-/three', '{"zero":[{"one": 1,"two": 2},{"three": 3,"four": 4}]}', [['three'=>3]]],
            'ISSUE-62#1' => ['/-/id', '[ {"id":125}, {"id":785}, {"id":459}, {"id":853} ]', [['id'=>125], ['id'=>785], ['id'=>459], ['id'=>853]]],
            'ISSUE-62#2' => ['/key/-/id', '{"key": [ {"id":125}, {"id":785}, {"id":459}, {"id":853} ]}', [['id'=>125], ['id'=>785], ['id'=>459], ['id'=>853]]],
        ];
    }

    /**
     * @dataProvider dataThrowsOnNotFoundJsonPointer
     * @param string $json
     * @param string $jsonPointer
     */
    public function testThrowsOnNotFoundJsonPointer($json, $jsonPointer)
    {
        $parser = $this->createParser($json, $jsonPointer);
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage("Path '$jsonPointer' was not found in json stream.");
        iterator_to_array($parser);
    }

    public function dataThrowsOnNotFoundJsonPointer()
    {
        return [
            "non existing pointer" => ['{}', '/not/found'],
            "empty string should not match '0'" => ['{"0":[]}', '/'],
            "empty string should not match 0" => ['[[]]', '/'],
            "0 should not match empty string" => ['{"":[]}', '/0'],
        ];
    }

    /**
     * @dataProvider dataGetJsonPointer
     * @param string $jsonPointer
     * @param array $expectedJsonPointer
     */
    public function testGetJsonPointerPath($jsonPointer, array $expectedJsonPointer)
    {
        $parser = $this->createParser('{}', $jsonPointer);
        $this->assertEquals($expectedJsonPointer, $parser->getJsonPointerPath());
    }

    public function dataGetJsonPointer()
    {
        return [
            ['/', ['']],
            ['////', ['', '', '', '']],
            ['/apple', ['apple']],
            ['/apple/pie', ['apple', 'pie']],
            ['/0/1   ', [0, '1   ']],
        ];
    }

    /**
     * @dataProvider dataThrowsOnMalformedJsonPointer
     * @param string $jsonPointer
     */
    public function testThrowsOnMalformedJsonPointer($jsonPointer)
    {
        $this->expectException(InvalidArgumentException::class);
        new Parser(new \ArrayObject(), $jsonPointer);
    }

    public function dataThrowsOnMalformedJsonPointer()
    {
        return [
            ['apple'],
            ['/apple/~'],
            ['apple/pie'],
            ['apple/pie/'],
            [' /apple/pie/'],
        ];
    }

    /**
     * @dataProvider dataSyntaxError
     * @param string $malformedJson
     */
    public function testSyntaxError($malformedJson)
    {
        $this->expectException(SyntaxError::class);

        iterator_to_array($this->createParser($malformedJson));
    }

    public function dataSyntaxError()
    {
        return [
            ['[}'],
            ['{]'],
            ['null'],
            ['true'],
            ['false'],
            ['0'],
            ['100'],
            ['"string"'],
            ['}'],
            [']'],
            [','],
            [':'],
            [''],
            ['[null null]'],
            ['["string" "string"]'],
            ['[,"string","string"]'],
            ['["string",,"string"]'],
            ['["string","string",]'],
            ['["string",1eeee1]'],
            ['{"key\u000Z": "non hex key"}']
        ];
    }

    /**
     * @dataProvider dataUnexpectedEndError
     * @param string $malformedJson
     */
    public function testUnexpectedEndError($malformedJson)
    {
        $this->expectException(UnexpectedEndSyntaxErrorException::class);

        iterator_to_array($this->createParser($malformedJson));
    }

    public function dataUnexpectedEndError()
    {
        return [
            ['['],
            ['{'],
            ['["string"'],
            ['["string",'],
            ['[{"string":"string"}'],
            ['[{"string":"string"},'],
            ['[{"string":"string"},{'],
            ['[{"string":"string"},{"str'],
            ['[{"string":"string"},{"string"'],
            ['{"string"'],
            ['{"string":'],
            ['{"string":"string"'],
            ['{"string":["string","string"]'],
            ['{"string":["string","string"'],
            ['{"string":["string","string",'],
            ['{"string":["string","string","str']
        ];
    }

    public function testGeneratorQuitsAfterFirstFoundCollectionHasBeenFinished()
    {
        $json = '
            {
                "results": [1],
                "other": [2],
                "results": [3]
            }
        ';

        $parser = $this->createParser($json, '/results');
        $this->assertSame([1], iterator_to_array($parser));
    }

    public function testScalarResult()
    {
        $result = $this->createParser('{"result":{"items": [1,2,3],"count": 3}}', '/result/count');
        $this->assertSame([3], iterator_to_array($result));
    }

    public function testScalarResultInArray()
    {
        $result = $this->createParser('{"result":[1,2,3]}', '/result/0');
        $this->assertSame([1], iterator_to_array($result));
    }

    public function testGeneratorQuitsAfterFirstScalarHasBeenFound()
    {
        $json = '
            {
                "result": "one",
                "other": [2],
                "result": "three"
            }
        ';

        $parser = $this->createParser($json, '/result');
        $this->assertSame(["result" => "one"], iterator_to_array($parser));
    }

    public function testGeneratorYieldsNestedValues()
    {
        $json = '
            {
                "zero": [
                    {
                        "one": "ignored",
                        "two": [
                            {
                                "three": 1
                            }
                        ],
                        "four": [
                            {
                                "five": "ignored"
                            }
                        ]
                    },
                    {
                        "one": 1,
                        "two": [
                            {
                                "three": 2
                            },
                            {
                                "three": 3
                            }
                        ],
                        "four": [
                            {
                                "five": "ignored"
                            }
                        ]
                    }
                ]
            }
        ';

        $parser = $this->createParser($json, '/zero/-/two/-/three');

        $i = 0;
        $expectedKey = 'three';
        $expectedValues = [1, 2, 3];

        foreach ($parser as $key => $value) {
            $this->assertSame($expectedKey, $key);
            $this->assertSame($expectedValues[$i++], $value);
        }
    }

    private function createParser($json, $jsonPointer = '')
    {
        return new Parser(new Lexer(new \ArrayIterator([$json])), $jsonPointer);
    }
}
