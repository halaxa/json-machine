<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxErrorException;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\Parser;
use JsonMachine\StringChunks;
use JsonMachine\Tokens;
use JsonMachine\TokensWithDebugging;

/**
 * @covers \JsonMachine\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testSyntax
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

    public function data_testSyntax()
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
            ['/~01', '{"~1":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
            ['/~00', '{"~0":{"c":1,"d":2}}', [['c' => 1], ['d' => 2]]],
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
     * @dataProvider data_testThrowsOnNotFoundJsonPointer
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

    public function data_testThrowsOnNotFoundJsonPointer()
    {
        return [
            'non existing pointer' => ['{}', '/not/found'],
            "empty string should not match '0'" => ['{"0":[]}', '/'],
            'empty string should not match 0 index' => ['[[]]', '/'],
            '0 should not match empty string' => ['{"":[]}', '/0'],
        ];
    }

    /**
     * @dataProvider data_testSyntaxError
     *
     * @param string $malformedJson
     */
    public function testSyntaxError($malformedJson)
    {
        $this->expectException(SyntaxErrorException::class);

        iterator_to_array($this->createParser($malformedJson));
    }

    public function data_testSyntaxError()
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
     * @dataProvider data_testUnexpectedEndError
     *
     * @param string $malformedJson
     */
    public function testUnexpectedEndError($malformedJson)
    {
        $this->expectException(UnexpectedEndSyntaxErrorException::class);

        iterator_to_array($this->createParser($malformedJson));
    }

    public function data_testUnexpectedEndError()
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

        $actual = [];
        $expected = ['three' => [1, 2, 3]];

        foreach ($parser as $key => $value) {
            $actual[$key][] = $value;
        }

        $this->assertSame($expected, $actual);
    }

    public function testGeneratorYieldsNestedValuesOfMultiplePaths()
    {
        $json = '
            {
                "zero": [
                    {
                        "one": "hello",
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
                        "one": "bye",
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

        $parser = $this->createParser($json, ['/zero/-/one', '/zero/-/two/-/three']);

        $actual = [];
        $expected = ['one' => ['hello', 'bye'], 'three' => [1, 2, 3]];

        foreach ($parser as $key => $value) {
            $actual[$key][] = $value;
        }

        $this->assertSame($expected, $actual);
    }

    private function createParser($json, $jsonPointer = '')
    {
        return new Parser(new Tokens(new \ArrayIterator([$json])), $jsonPointer, new ExtJsonDecoder(true));
    }

    public function testDefaultDecodingStructureIsObject()
    {
        $items = new Parser(new Tokens(new StringChunks('[{"key": "value"}]')));

        foreach ($items as $item) {
            $this->assertEquals((object) ['key' => 'value'], $item);
        }
    }

    /**
     * @dataProvider data_testGetCurrentJsonPointer
     */
    public function testGetCurrentJsonPointer($jsonPointer, string $json, array $currentJsonPointers)
    {
        $parser = $this->createParser($json, $jsonPointer);

        $i = 0;

        foreach ($parser as $value) {
            $this->assertEquals($currentJsonPointers[$i++], $parser->getCurrentJsonPointer());
        }
    }

    public function data_testGetCurrentJsonPointer()
    {
        return [
            ['', '{"c":1,"d":2}', ['', '']],
            ['/', '{"":{"c":1,"d":2}}', ['/', '/']],
            ['/~0', '{"~":{"c":1,"d":2}}', ['/~0', '/~0']],
            ['/~1', '{"/":{"c":1,"d":2}}', ['/~1', '/~1']],
            ['/~01', '{"~1":{"c":1,"d":2}}', ['/~01', '/~01']],
            ['/~00', '{"~0":{"c":1,"d":2}}', ['/~00', '/~00']],
            ['/~1/c', '{"/":{"c":[1,2],"d":2}}', ['/~1/c', '/~1/c']],
            ['/0', '[{"c":1,"d":2}, [null]]', ['/0', '/0']],
            ['/-', '[{"one": 1,"two": 2},{"three": 3,"four": 4}]', ['/0', '/0', '/1', '/1']],
            [
                ['/two', '/four'],
                '{"one": [1,11], "two": [2,22], "three": [3,33], "four": [4,44]}',
                ['/two', '/two', '/four', '/four'],
            ],
            [
                ['/-/two', '/-/one'],
                '[{"one": 1, "two": 2}, {"one": 1, "two": 2}]',
                ['/0/one', '/0/two', '/1/one', '/1/two'],
            ],
        ];
    }

    /**
     * @dataProvider data_testGetMatchedJsonPointer
     */
    public function testGetMatchedJsonPointer($jsonPointer, string $json, array $matchedJsonPointers)
    {
        $parser = $this->createParser($json, $jsonPointer);

        $i = 0;

        foreach ($parser as $value) {
            $this->assertEquals($matchedJsonPointers[$i++], $parser->getMatchedJsonPointer());
        }
    }

    public function data_testGetMatchedJsonPointer()
    {
        return [
            ['', '{"c":1,"d":2}', ['', '']],
            ['/', '{"":{"c":1,"d":2}}', ['/', '/']],
            ['/~0', '{"~":{"c":1,"d":2}}', ['/~0', '/~0']],
            ['/~1', '{"/":{"c":1,"d":2}}', ['/~1', '/~1']],
            ['/~01', '{"~1":{"c":1,"d":2}}', ['/~01', '/~01']],
            ['/~00', '{"~0":{"c":1,"d":2}}', ['/~00', '/~00']],
            ['/~1/c', '{"/":{"c":[1,2],"d":2}}', ['/~1/c', '/~1/c']],
            ['/0', '[{"c":1,"d":2}, [null]]', ['/0', '/0']],
            ['/-', '[{"one": 1,"two": 2},{"three": 3,"four": 4}]', ['/-', '/-', '/-', '/-']],
            [
                ['/two', '/four'],
                '{"one": [1,11], "two": [2,22], "three": [3,33], "four": [4,44]}',
                ['/two', '/two', '/four', '/four'],
            ],
            [
                ['/-/two', '/-/one'],
                '[{"one": 1, "two": 2}, {"one": 1, "two": 2}]',
                ['/-/one', '/-/two', '/-/one', '/-/two'],
            ],
        ];
    }

    public function testGetCurrentJsonPointerThrowsWhenCalledOutsideOfALoop()
    {
        $this->expectException(JsonMachineException::class);
        $this->expectExceptionMessage('must be called inside a loop');
        $parser = $this->createParser('[]');
        $parser->getCurrentJsonPointer();
    }

    public function testGetCurrentJsonPointerReturnsLiteralJsonPointer()
    {
        $parser = $this->createParser('{"\"key\\\\":"value"}', ['/\"key\\\\']);

        foreach ($parser as $key => $item) {
            $this->assertSame('/\"key\\\\', $parser->getCurrentJsonPointer());
        }
    }

    public function testGetMatchedJsonPointerThrowsWhenCalledOutsideOfALoop()
    {
        $this->expectException(JsonMachineException::class);
        $this->expectExceptionMessage('must be called inside a loop');
        $parser = $this->createParser('[]');
        $parser->getMatchedJsonPointer();
    }

    public function testGetMatchedJsonPointerReturnsLiteralMatch()
    {
        $parser = $this->createParser('{"\"key\\\\":"value"}', ['/\"key\\\\']);

        foreach ($parser as $key => $item) {
            $this->assertSame('/\"key\\\\', $parser->getMatchedJsonPointer());
        }
    }

    public function testGetJsonPointers()
    {
        $parser = $this->createParser('{}', ['/one', '/two']);
        $this->assertSame(['/one', '/two'], $parser->getJsonPointers());

        $parser = $this->createParser('{}');
        $this->assertSame([''], $parser->getJsonPointers());
    }

    public function testJsonPointerReferenceTokenMatchesJsonMemberNameLiterally()
    {
        $parser = $this->createParser('{"\\"key":"value"}', ['/\\"key']);

        foreach ($parser as $key => $item) {
            $this->assertSame('"key', $key);
            $this->assertSame('value', $item);
        }
    }

    public function testGetPositionReturnsCorrectPositionWithDebugEnabled()
    {
        $parser = new Parser(new TokensWithDebugging(['[   1, "two", false ]']));
        $expectedPosition = [5, 12, 19];

        $this->assertSame(0, $parser->getPosition());
        foreach ($parser as $index => $item) {
            $this->assertSame($expectedPosition[$index], $parser->getPosition(), "index:$index, item:$item");
        }
        $this->assertSame(21, $parser->getPosition());
    }

    public function testGetPositionReturns0WithDebugDisabled()
    {
        $parser = new Parser(new Tokens(['[   1, "two", false ]']));

        $this->assertSame(0, $parser->getPosition());
        foreach ($parser as $index => $item) {
            $this->assertSame(0, $parser->getPosition());
        }
        $this->assertSame(0, $parser->getPosition());
    }

    public function testGetPositionThrowsIfTokensDoNotSupportGetPosition()
    {
        $parser = new Parser(new \ArrayObject());

        $this->expectException(JsonMachineException::class);
        $parser->getPosition();
    }

    public function testThrowsMeaningfulErrorOnIncorrectTokens()
    {
        $parser = new Parser(new Tokens(['[$P]']));

        $this->expectException(SyntaxErrorException::class);

        foreach ($parser as $index => $item) {
        }
    }
}
