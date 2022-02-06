<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\ValidResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\ValidResult
 */
class ValidResultTest extends TestCase
{
    public function testGetValue()
    {
        $result = new ValidResult('Value X');

        $this->assertSame('Value X', $result->getValue());
    }

    public function testIsOk()
    {
        $result = new ValidResult('X');

        $this->assertTrue($result->isOk());
    }
}
