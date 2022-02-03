<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use PHPUnit_Framework_TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\ExtJsonDecoder
 */
class ExtJsonDecoderTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultOptions()
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $noOptsDecoder = new ExtJsonDecoder();
        $defaultResult = $noOptsDecoder->decode($json);
        $this->assertTrue('object' === gettype($defaultResult->getValue()));
        $this->assertFalse('string' === gettype($defaultResult->getValue()->bigint));
        $this->assertSame([['deeper']], $defaultResult->getValue()->deep);
    }

    public function testPassesAssocTrueOptionToJsonDecode()
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $assocDecoder = new ExtJsonDecoder(true);
        $assocResult = $assocDecoder->decode($json);
        $this->assertTrue('array' === gettype($assocResult->getValue()));
    }

    public function testPassesAssocFalseOptionToJsonDecode()
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $objDecoder = new ExtJsonDecoder(false);
        $objResult = $objDecoder->decode($json);
        $this->assertTrue('object' === gettype($objResult->getValue()));
    }

    public function testPassesPassesDepthOptionToJsonDecode()
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $depthDecoder = new ExtJsonDecoder(true, 1);
        $depthResult = $depthDecoder->decode($json);
        $this->assertFalse($depthResult->isOk());
        $this->assertSame('Maximum stack depth exceeded', $depthResult->getErrorMessage());
    }

    public function testPassesPassesBigIntOptionToJsonDecode()
    {
        $bigintDecoder = new ExtJsonDecoder(false, 1, JSON_BIGINT_AS_STRING);
        $bigintResult = $bigintDecoder->decode('123123123123123123123');
        $this->assertSame('123123123123123123123', $bigintResult->getValue());
    }
}
