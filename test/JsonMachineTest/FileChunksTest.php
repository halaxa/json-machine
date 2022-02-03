<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\FileChunks;

/**
 * @covers \JsonMachine\FileChunks
 */
class FileChunksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testGeneratorYieldsFileChunks
     */
    public function testGeneratorYieldsStringChunks($chunkSize, array $expectedResult)
    {
        $fileChunks = new FileChunks(__DIR__.'/ItemsTest.json', $chunkSize);
        $result = iterator_to_array($fileChunks);

        $this->assertSame($expectedResult, $result);
    }

    public function data_testGeneratorYieldsFileChunks()
    {
        return [
            [5, ['{"pat', 'h": {', '"key"', ':"val', 'ue"}}', "\n"]],
            [6, ['{"path', '": {"k', 'ey":"v', 'alue"}', '}'."\n"]],
            [1024, ['{"path": {"key":"value"}}'."\n"]],
        ];
    }
}
