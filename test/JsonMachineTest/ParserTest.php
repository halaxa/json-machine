<?php

namespace JsonMachineTest;

use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\Lexer;
use JsonMachine\Parser;
use JsonMachine\StringChunks;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataSyntax
     *
     * @param string $jsonPointer
     * @param string $json
     * @param array  $expectedResult
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
            ['', '{"a": "b"}', [['a' => 'b']]],
            ['', '{"a":{"b":{"c":1}}}', [['a' => ['b' => ['c' => 1]]]]],
            ['', '[]', []],
            ['', '[null,true,false,"a",0,1,42.5]', [[0 => null], [1 => true], [2 => false], [3 => 'a'], [4 => 0], [5 => 1], [6 => 42.5]]],
            ['', '[{"c":1}]', [[['c' => 1]]]],
            ['', '[{"c":1},"string",{"d":2},false]', [[0 => ['c' => 1]], [1 => 'string'], [2 => ['d' => 2]], [3 => false]]],
            ['', '[false,{"c":1},"string",{"d":2}]', [[0 => false], [1 => ['c' => 1]], [2 => 'string'], [3 => ['d' => 2]]]],
            ['', '[{"c":1,"d":2}]', [[['c' => 1, 'd' => 2]]]],
            ['/', '{"":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/~0', '{"~":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/~1', '{"/":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/path', '{"path":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/path', '{"no":[null], "path":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/0', '[{"c":1,"d":2}, [null]]', [['c' => 1], ['d' => 2]]],
            ['/0/path', '[{"path":{"c":1,"d":2}}]', [['c' => 1], ['d' => 2]]],
            ['/1/path', '[[null], {"path":{"c":1,"d":2}}]', [['c' => 1], ['d' => 2]]],
            ['/path/0', '{"path":[{"c":1,"d":2}, [null]]}', [['c' => 1], ['d' => 2]]],
            ['/path/1', '{"path":[null,{"c":1,"d":2}, [null]]}', [['c' => 1], ['d' => 2]]],
            ['/path/to', '{"path":{"to":{"c":1,"d":2}}}', [['c' => 1], ['d' => 2]]],
            ['/path/after-vector', '{"path":{"array":[],"after-vector":{"c":1,"d":2}}}', [['c' => 1], ['d' => 2]]],
            ['/path/after-vector', '{"path":{"array":["item"],"after-vector":{"c":1,"d":2}}}', [['c' => 1], ['d' => 2]]],
            ['/path/after-vector', '{"path":{"object":{"item":null},"after-vector":{"c":1,"d":2}}}', [['c' => 1], ['d' => 2]]],
            ['/path/after-vectors', '{"path":{"array":[],"object":{},"after-vectors":{"c":1,"d":2}}}', [['c' => 1], ['d' => 2]]],
            ['/0/0', '[{"0":{"c":1,"d":2}}]', [['c' => 1], ['d' => 2]]],
            ['/1/1', '[0,{"1":{"c":1,"d":2}}]', [['c' => 1], ['d' => 2]]],
            'PR-19-FIX' => ['/datafeed/programs/1', file_get_contents(__DIR__.'/PR-19-FIX.json'), [['program_info' => ['id' => 'X1']]]],
            'ISSUE-41-FIX' => ['/path', '{"path":[{"empty":{}},{"value":1}]}', [[['empty' => []]], [1 => ['value' => 1]]]],
            ['/-', '[{"one": 1,"two": 2},{"three": 3,"four": 4}]', [['one' => 1], ['two' => 2], ['three' => 3], ['four' => 4]]],
            ['/zero/-', '{"zero":[{"one": 1,"two": 2},{"three": 3,"four": 4}]}', [['one' => 1], ['two' => 2], ['three' => 3], ['four' => 4]]],
            ['/zero/-/three', '{"zero":[{"one": 1,"two": 2},{"three": 3,"four": 4}]}', [['three' => 3]]],
            'ISSUE-62#1' => ['/-/id', '[ {"id":125}, {"id":785}, {"id":459}, {"id":853} ]', [['id' => 125], ['id' => 785], ['id' => 459], ['id' => 853]]],
            'ISSUE-62#2' => ['/key/-/id', '{"key": [ {"id":125}, {"id":785}, {"id":459}, {"id":853} ]}', [['id' => 125], ['id' => 785], ['id' => 459], ['id' => 853]]],
            [
                ['/meta_data', '/data/companies'],
                '{"meta_data": {"total_rows": 2},"data": {"type": "companies","companies": [{"id": "1","company": "Company 1"},{"id": "2","company": "Company 2"}]}}',
                [
                    ['total_rows' => 2],
                    ['0' => ['id' => '1', 'company' => 'Company 1']],
                    ['1' => ['id' => '2', 'company' => 'Company 2']],
                ],
            ],
            [
                ['/-/id', '/-/company'],
                '[{"id": "1","company": "Company 1"},{"id": "2","company": "Company 2"}]',
                [
                    ['id' => '1'],
                    ['company' => 'Company 1'],
                    ['id' => '2'],
                    ['company' => 'Company 2'],
                ],
            ],
            [
                ['/-/id', '/0/company'],
                '[{"id": "1","company": "Company 1"},{"id": "2","company": "Company 2"}]',
                [
                    ['id' => '1'],
                    ['company' => 'Company 1'],
                    ['id' => '2'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataThrowsOnNotFoundJsonPointer
     *
     * @param string $json
     * @param string $jsonPointer
     */
    public function testThrowsOnNotFoundJsonPointer($json, $jsonPointer)
    {
        $parser = $this->createParser($json, $jsonPointer);
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage("Paths '".implode(', ', (array) $jsonPointer)."' were not found in json stream.");
        iterator_to_array($parser);
    }

    public function dataThrowsOnNotFoundJsonPointer()
    {
        return [
            'non existing pointer' => ['{}', '/not/found'],
            "empty string should not match '0'" => ['{"0":[]}', '/'],
            'empty string should not match 0' => ['[[]]', '/'],
            '0 should not match empty string' => ['{"":[]}', '/0'],
        ];
    }

    /**
     * @dataProvider dataGetJsonPointerPath
     *
     * @param string $jsonPointer
     */
    public function testGetJsonPointerPath($jsonPointer, array $expectedJsonPointerPath)
    {
        $parser = $this->createParser('{}', $jsonPointer);
        $this->assertEquals($expectedJsonPointerPath, $parser->getJsonPointerPaths());
    }

    public function dataGetJsonPointerPath()
    {
        return [
            ['/', ['/' => ['']]],
            ['////', ['////' => ['', '', '', '']]],
            ['/apple', ['/apple' => ['apple']]],
            ['/apple/pie', ['/apple/pie' => ['apple', 'pie']]],
            ['/0/1   ', ['/0/1   ' => [0, '1   ']]],
            [['/apple/pie', '/banana'], ['/apple/pie' => ['apple', 'pie'], '/banana' => ['banana']]],
        ];
    }

    /**
     * @dataProvider dataSyntaxError
     *
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
            ['{"key\u000Z": "non hex key"}'],
        ];
    }

    /**
     * @dataProvider dataUnexpectedEndError
     *
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
            ['{"string":["string","string","str'],
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
        $this->assertSame(['result' => 'one'], iterator_to_array($parser));
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
        return new Parser(new Lexer(new \ArrayIterator([$json])), $jsonPointer, new ExtJsonDecoder(true));
    }

    public function testDefaultDecodingStructureIsObject()
    {
        $items = new Parser(new Lexer(new StringChunks('[{"key": "value"}]')));

        foreach ($items as $item) {
            $this->assertEquals((object) ['key' => 'value'], $item);
        }
    }

    /**
     * @dataProvider dataGetCurrentAndMatchedJsonPointer
     */
    public function testGetCurrentAndMatchedJsonPointer(string $jsonPointer, string $json, array $expectedJsonPointers)
    {
        $parser = $this->createParser($json, $jsonPointer);

        $i = 0;

        foreach ($parser as $value) {
            $this->assertEquals($expectedJsonPointers[$i++] ?? '', $parser->getCurrentJsonPointer());
            $this->assertEquals($jsonPointer, $parser->getMatchedJsonPointer());
        }
    }

    public function dataGetCurrentAndMatchedJsonPointer()
    {
        return [
            ['', '{"c":1,"d":2}', ['', '']],
            ['/', '{"":{"c":1,"d":2}}', ['/', '/']],
            ['/~0', '{"~":{"c":1,"d":2}}', ['/~0', '/~0']],
            ['/~1', '{"/":{"c":1,"d":2}}', ['/~1', '/~1']],
            ['/~1/c', '{"/":{"c":[1,2],"d":2}}', ['/~1/c', '/~1/c']],
            ['/0', '[{"c":1,"d":2}, [null]]', ['/0', '/0']],
            ['/-', '[{"one": 1,"two": 2},{"three": 3,"four": 4}]', ['/0', '/0', '/1', '/1']],
        ];
    }

    public function testGetCurrentJsonPointerThrowsWhenCalledOutsideOfALoop()
    {
        $this->expectException(JsonMachineException::class);
        $this->expectExceptionMessage('getCurrentJsonPointer() must not be called outside of a loop');
        $parser = $this->createParser('[]');
        $parser->getCurrentJsonPointer();
    }

    public function testGetMatchedJsonPointerThrowsWhenCalledOutsideOfALoop()
    {
        $this->expectException(JsonMachineException::class);
        $this->expectExceptionMessage('getMatchedJsonPointer() must not be called outside of a loop');
        $parser = $this->createParser('[]');
        $parser->getMatchedJsonPointer();
    }

    public function testGetJsonPointer()
    {
        $parser = $this->createParser('{}', ['/one']);

        $this->assertSame('/one', $parser->getJsonPointer());
    }

    public function testGetJsonPointerReturnsDefaultJsonPointer()
    {
        $parser = $this->createParser('{}');

        $this->assertSame('', $parser->getJsonPointer());
    }

    public function testGetJsonPointerThrowsOnMultipleJsonPointers()
    {
        $this->expectException(JsonMachineException::class);
        $this->expectExceptionMessage('Call getJsonPointers() when you provide more than one.');
        $parser = $this->createParser('{}', ['/one', '/two']);
        $parser->getJsonPointer();
    }
}
