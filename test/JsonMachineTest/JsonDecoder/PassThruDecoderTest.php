<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\PassThruDecoder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\PassThruDecoder
 */
class PassThruDecoderTest extends TestCase
{
    public function testPassThruDecode()
    {
        $passThruDecoder = new PassThruDecoder();
        $passThruResult = $passThruDecoder->decode('["json"]');
        $this->assertSame('["json"]', $passThruResult->getValue());
    }
}
