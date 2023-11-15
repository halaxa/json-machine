<?php

declare(strict_types=1);

namespace JsonMachineTest\Exception;

use JsonMachine\Exception\SyntaxErrorException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\Exception\SyntaxErrorException
 */
class SyntaxErrorExceptionTest extends TestCase
{
    public function testMessageContainsDataFromConstructor()
    {
        $exception = new SyntaxErrorException('msg 42', 24);

        $this->assertContains('msg 42', $exception->getMessage());
        $this->assertContains('24', $exception->getMessage());
    }
}
