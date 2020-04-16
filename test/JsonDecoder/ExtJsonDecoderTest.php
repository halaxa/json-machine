<?php

use JsonMachine\JsonDecoder\ExtJsonDecoder;

class ExtJsonDecoderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataPassesOptionsToJsonDecode
     */
    public function testPassesOptionsToJsonDecode($methodName)
    {
        $json = '{"bigint": 123456789123456789123456789, "deep": [["deeper"]]}';

        $defaultDecoder = new ExtJsonDecoder();
        $defaultResult = $defaultDecoder->$methodName($json);
        $this->assertInternalType("object", $defaultResult->getValue());
        $this->assertNotInternalType("string", $defaultResult->getValue()->bigint);
        $this->assertSame([["deeper"]], $defaultResult->getValue()->deep);

        $assocDecoder = new ExtJsonDecoder(true);
        $assocResult = $assocDecoder->$methodName($json);
        $this->assertInternalType("array", $assocResult->getValue());

        $objDecoder = new ExtJsonDecoder(false);
        $objResult = $objDecoder->$methodName($json);
        $this->assertInternalType("object", $objResult->getValue());

        $depthDecoder = new ExtJsonDecoder(true, 1);
        $depthResult = $depthDecoder->$methodName($json);
        $this->assertFalse($depthResult->isOk());
        $this->assertSame("Maximum stack depth exceeded", $depthResult->getErrorMessage());

        $bigintDecoder = new ExtJsonDecoder(null, 1, JSON_BIGINT_AS_STRING);
        $bigintResult = $bigintDecoder->$methodName("123123123123123123123");
        $this->assertSame("123123123123123123123", $bigintResult->getValue());
    }

    public function dataPassesOptionsToJsonDecode()
    {
        return [
            ['decodeKey'],
            ['decodeValue'],
        ];
    }
}
