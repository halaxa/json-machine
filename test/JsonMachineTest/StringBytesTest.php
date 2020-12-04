<?php

namespace JsonMachineTest;

use JsonMachine\StringBytes;

class StringBytesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testGeneratorYieldsStringChunks
     */
    public function testGeneratorYieldsStringChunks($string, $chunkSize, array $expectedResult)
    {
        $stringBytes = new StringBytes($string, $chunkSize);
        $result = iterator_to_array($stringBytes);

        $this->assertSame($expectedResult, $result);
    }

    public function data_testGeneratorYieldsStringChunks()
    {
        return [
            // single-byte:
            ['onetwo', 6, ['onetwo']],
            ['onetwo', 7, ['onetwo']],
            ['onetwo', 3, ['one', 'two']],
            ['onetwo', 4, ['onet', 'wo']],
        ];
    }
}
