<?php

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\PassThruDecoder;
use PHPUnit_Framework_TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\ExtJsonDecoder
 * @covers \JsonMachine\JsonDecoder\PassThruDecoder
 */
class ExtJsonDecoderTest extends PHPUnit_Framework_TestCase
{
    public function testPassesOptionsToJsonDecode()
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $noOptsDecoder = new ExtJsonDecoder();
        $defaultResult = $noOptsDecoder->decode($json);
        $this->assertTrue('object' === gettype($defaultResult->getValue()));
        $this->assertFalse('string' === gettype($defaultResult->getValue()->bigint));
        $this->assertSame([['deeper']], $defaultResult->getValue()->deep);

        $assocDecoder = new ExtJsonDecoder(true);
        $assocResult = $assocDecoder->decode($json);
        $this->assertTrue('array' === gettype($assocResult->getValue()));

        $objDecoder = new ExtJsonDecoder(false);
        $objResult = $objDecoder->decode($json);
        $this->assertTrue('object' === gettype($objResult->getValue()));

        $depthDecoder = new ExtJsonDecoder(true, 1);
        $depthResult = $depthDecoder->decode($json);
        $this->assertFalse($depthResult->isOk());
        $this->assertSame('Maximum stack depth exceeded', $depthResult->getErrorMessage());

        $bigintDecoder = new ExtJsonDecoder(null, 1, JSON_BIGINT_AS_STRING);
        $bigintResult = $bigintDecoder->decode('123123123123123123123');
        $this->assertSame('123123123123123123123', $bigintResult->getValue());
    }

    public function testPassThruDecode()
    {
        $passThruDecoder = new PassThruDecoder();
        $passThruResult = $passThruDecoder->decode('["json"]');
        $this->assertSame('["json"]', $passThruResult->getValue());
    }
}
