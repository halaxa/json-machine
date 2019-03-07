<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Lexer;
use JsonMachine\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataSyntax
     */
    public function testSyntax($pathSpec, $json, $expectedResult)
    {
        $resultWithKeys = iterator_to_array($this->createParser($json, $pathSpec));
        $resultNoKeys = iterator_to_array($this->createParser($json, $pathSpec), false);

        $this->assertEquals($expectedResult, $resultWithKeys);
        $this->assertEquals(array_values($expectedResult), $resultNoKeys);
    }

    public function dataSyntax()
    {
        return [
            ['', '{}', []],
            ['', '{"a": "b"}', ['a'=>'b']],
            ['', '{"a":{"b":{"c":1}}}', ['a'=>['b'=>['c'=>1]]]],
            ['', '[]', []],
            ['', '[null,true,false,"a",0,1,42.5]', [null,true,false,"a",0,1,42.5]],
            ['', '[{"c":1}]', [['c'=>1]]],
            ['', '[{"c":1},"string",{"d":2},false]', [['c'=>1],"string",['d'=>2],false]],
            ['', '[false,{"c":1},"string",{"d":2}]', [false,['c'=>1],"string",['d'=>2]]],
            ['', '[{"c":1,"d":2}]', [['c'=>1,'d'=>2]]],
            ['/', '{"":{"c":1,"d":2}}', ['c'=>1,'d'=>2]],
            ['/~0', '{"~":{"c":1,"d":2}}', ['c'=>1,'d'=>2]],
            ['/~1', '{"/":{"c":1,"d":2}}', ['c'=>1,'d'=>2]],
            ['/path', '{"path":{"c":1,"d":2}}', ['c'=>1,'d'=>2]],
            ['/path', '{"no":[null], "path":{"c":1,"d":2}}', ['c'=>1,'d'=>2]],
            ['/0', '[{"c":1,"d":2}, [null]]', ['c'=>1,'d'=>2]],
            ['/0/path', '[{"path":{"c":1,"d":2}}]', ['c'=>1,'d'=>2]],
            ['/1/path', '[[null], {"path":{"c":1,"d":2}}]', ['c'=>1,'d'=>2]],
            ['/path/0', '{"path":[{"c":1,"d":2}, [null]]}', ['c'=>1,'d'=>2]],
            ['/path/1', '{"path":[null,{"c":1,"d":2}, [null]]}', ['c'=>1,'d'=>2]],
            ['/path/to', '{"path":{"to":{"c":1,"d":2}}}', ['c'=>1,'d'=>2]],
            ['/path/after-vector', '{"path":{"array":[],"after-vector":{"c":1,"d":2}}}', ['c'=>1,'d'=>2]],
            ['/path/after-vector', '{"path":{"array":["item"],"after-vector":{"c":1,"d":2}}}', ['c'=>1,'d'=>2]],
            ['/path/after-vector', '{"path":{"object":{"item":null},"after-vector":{"c":1,"d":2}}}', ['c'=>1,'d'=>2]],
            ['/path/after-vectors', '{"path":{"array":[],"object":{},"after-vectors":{"c":1,"d":2}}}', ['c'=>1,'d'=>2]],
            ['/0/0', '[{"0":{"c":1,"d":2}}]', ['c'=>1,'d'=>2]],
            ['/1/1', '[0,{"1":{"c":1,"d":2}}]', ['c'=>1,'d'=>2]],
        ];
    }

    public function testThrowsOnNotFoundPathSpec()
    {
        $parser = $this->createParser('{}', '/not/found');
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessage("Path '/not/found' was not found in json stream.");
        iterator_to_array($parser);
    }

    /**
     * @dataProvider dataGetPathSpec
     */
    public function testGetPathSpec($pathSpec, array $expectedPathSpec)
    {
        $parser = $this->createParser('{}', $pathSpec);
        $this->assertEquals($expectedPathSpec, $parser->getJsonPointerPath());
    }

    public function dataGetPathSpec()
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
     */
    public function testSyntaxError($notIterableJson, $exception = SyntaxError::class)
    {
        $this->expectException($exception);

        iterator_to_array($this->createParser($notIterableJson));
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
        ];
    }

    private function createParser($json, $jsonPointer = '')
    {
        return new Parser(new Lexer(new \ArrayIterator([$json])), $jsonPointer);
    }
}
