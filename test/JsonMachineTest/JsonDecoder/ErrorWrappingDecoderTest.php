<?php

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\Decoder;
use JsonMachine\JsonDecoder\DecodingError;
use JsonMachine\JsonDecoder\DecodingResult;
use JsonMachine\JsonDecoder\ErrorWrappingDecoder;
use PHPUnit_Framework_TestCase;

class ErrorWrappingDecoderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testTrueFalseMatrix
     * @param array $mockMethods
     */
    public function testTrueFalseMatrix(array $mockMethods)
    {
        $innerDecoderMock = $this->createMock(Decoder::class);
        $innerDecoderMock->method('decodeValue')->willReturn($mockMethods['decodeValue']);
        $innerDecoderMock->method('decodeKey')->willReturn($mockMethods['decodeKey']);

        $decoder = new ErrorWrappingDecoder($innerDecoderMock);

        $keyResult = $decoder->decodeKey('"json"');
        $valueResult = $decoder->decodeValue('"json"');

        $this->assertTrue($keyResult->isOk());
        $this->assertTrue($valueResult->isOk());
        $this->assertEquals($mockMethods['wrappedDecodeValue'], $valueResult);
        $this->assertEquals($mockMethods['wrappedDecodeKey'], $keyResult);
    }

    public function data_testTrueFalseMatrix()
    {
        $notOkResult = new DecodingResult(false, null, 'Error happened.');
        $okResult = new DecodingResult(true, 'json');
        $wrappedNotOkResult = new DecodingResult(true, new DecodingError('"json"', 'Error happened.'));
        $wrappedOkResult = $okResult;

        return [
            [
                [
                    'decodeValue' => $notOkResult,
                    'decodeKey'   => $notOkResult,
                    'wrappedDecodeValue' => $wrappedNotOkResult,
                    'wrappedDecodeKey' => $wrappedNotOkResult,
                ]
            ],
            [
                [
                    'decodeValue' => $notOkResult,
                    'decodeKey'   => $okResult,
                    'wrappedDecodeValue' => $wrappedNotOkResult,
                    'wrappedDecodeKey' => $wrappedOkResult,
                ]
            ],
            [
                [
                    'decodeValue' => $okResult,
                    'decodeKey'   => $notOkResult,
                    'wrappedDecodeValue' => $wrappedOkResult,
                    'wrappedDecodeKey' => $wrappedNotOkResult,
                ]
            ],
            [
                [
                    'decodeValue' => $okResult,
                    'decodeKey'   => $okResult,
                    'wrappedDecodeValue' => $wrappedOkResult,
                    'wrappedDecodeKey' => $wrappedOkResult,
                ]
            ],
        ];
    }
}
