<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\ValidJsonPointers;

/**
 * @covers \JsonMachine\ValidJsonPointers
 */
class ValidJsonPointersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testThrowsOnIntersectingPaths
     *
     * @param $jsonPointers
     */
    public function testThrowsOnIntersectingPaths($jsonPointers)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'$jsonPointers[0]', '$jsonPointers[1]'");
        (new ValidJsonPointers($jsonPointers))->toArray();
    }

    public function data_testThrowsOnIntersectingPaths()
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
     * @dataProvider data_testThrowsOnMalformedJsonPointer
     */
    public function testThrowsOnMalformedJsonPointer(array $jsonPointer)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not valid');
        (new ValidJsonPointers($jsonPointer))->toArray();
    }

    public function data_testThrowsOnMalformedJsonPointer()
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

    /**
     * @dataProvider data_testToArrayReturnsJsonPointers
     */
    public function testToArrayReturnsJsonPointers(string $pointerA, string $pointerB)
    {
        $this->assertSame(
            [$pointerA, $pointerB],
            (new ValidJsonPointers([$pointerA, $pointerB]))->toArray()
        );
    }

    public function data_testToArrayReturnsJsonPointers()
    {
        return [
            ['/one', '/two'],
            ['/companies/-/id', '/companies/0/idempotency_key'],
            ['/companies/1/id', '/companies/1/idempotency_key'],
        ];
    }
}
