<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\StreamBytes;
use PHPUnit\Framework\TestCase;

class StreamBytesTest extends TestCase
{
    public function testThrowsIfNoResource()
    {
        $this->expectException(InvalidArgumentException::class);
        new StreamBytes(false);
    }

    public function testGeneratorYieldsData()
    {
        $result = iterator_to_array(new StreamBytes(fopen('data://text/plain,test', 'r')));
        $this->assertSame(['test'], $result);
    }
}
