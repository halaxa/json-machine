<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\InvalidResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\InvalidResult
 */
class InvalidResultTest extends TestCase
{
    public function testGetErrorMessage()
    {
        $result = new InvalidResult('Error X');

        $this->assertSame('Error X', $result->getErrorMessage());
    }

    public function testIsOk()
    {
        $result = new InvalidResult('X');

        $this->assertFalse($result->isOk());
    }
}
