<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\StreamBytes;

class StreamBytesTest extends \PHPUnit_Framework_TestCase
{
    public function testThrowsIfNoResource()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new StreamBytes(false);
    }

    public function testGeneratorYieldsData()
    {
        $result = iterator_to_array(new StreamBytes(fopen('data://text/plain,test', 'r')));
        $this->assertSame(['test'], $result);
    }
}
