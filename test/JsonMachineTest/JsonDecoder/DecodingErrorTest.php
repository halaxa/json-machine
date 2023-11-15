<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\DecodingError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\DecodingError
 */
class DecodingErrorTest extends TestCase
{
    public function testGetMalformedJson()
    {
        $decodingError = new DecodingError('"json\"', '');

        $this->assertSame('"json\"', $decodingError->getMalformedJson());
    }

    public function testGetErrorMessage()
    {
        $decodingError = new DecodingError('', 'something bad happened');

        $this->assertSame('something bad happened', $decodingError->getErrorMessage());
    }
}
