<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\ValidJsonPointers;

class ValidJsonPointersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataIntersectingPaths
     *
     * @param $jsonPointers
     * @param ParserTest $parserTest
     */
    public function testThrowsOnIntersectingPaths($jsonPointers)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'$jsonPointers[0]', '$jsonPointers[1]'");
        (new ValidJsonPointers($jsonPointers))->toArray();
    }

    public function dataIntersectingPaths()
    {
        return [
            [['/companies/-/id', '/companies/0/id']],
            [['/companies/-/id', '', '/companies/0/id']],
            [['/companies/-/id', '']],
            [['/companies/0/id', '']],
            [['//in-empty-string-key', '/']],
            [['/~0~1/in-escaped-key', '/~0~1']],
        ];
    }

    /**
     * @dataProvider dataThrowsOnMalformedJsonPointer
     */
    public function testThrowsOnMalformedJsonPointer(array $jsonPointer)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not valid');
        (new ValidJsonPointers($jsonPointer))->toArray();
    }

    public function dataThrowsOnMalformedJsonPointer()
    {
        return [
            [['apple']],
            [['/apple/~']],
            [['apple/pie']],
            [['apple/pie/']],
            [[' /apple/pie/']],
            [[
                '/valid',
                '/valid/-',
                'inv/alid',
            ]],
        ];
    }

    public function testThrowsOnDuplicatePaths()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'/one', '/one'");
        (new ValidJsonPointers(['/one', '/one']))->toArray();
    }

    public function testToArrayReturnsJsonPointers()
    {
        $this->assertSame(
            ['/one', '/two'],
            (new ValidJsonPointers(['/one', '/two']))->toArray()
        );
    }
}
