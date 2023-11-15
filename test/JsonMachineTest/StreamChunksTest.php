<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\StreamChunks;

/**
 * @covers \JsonMachine\StreamChunks
 */
class StreamChunksTest extends \PHPUnit_Framework_TestCase
{
    public function testThrowsIfNoResource()
    {
        $this->expectException(InvalidArgumentException::class);
        /* @phpstan-ignore-next-line */
        new StreamChunks(false);
    }

    public function testGeneratorYieldsData()
    {
        $result = iterator_to_array(new StreamChunks(fopen('data://text/plain,test', 'r')));
        $this->assertSame(['test'], $result);
    }
}
