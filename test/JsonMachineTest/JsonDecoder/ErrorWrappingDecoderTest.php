<?php

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\DecodingError;
use JsonMachine\JsonDecoder\DecodingResult;
use JsonMachine\JsonDecoder\ErrorWrappingDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonMachine;
use PHPUnit_Framework_TestCase;

class ErrorWrappingDecoderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testTrueFalseMatrix
     * @param array $case
     */
    public function testTrueFalseMatrix(array $case)
    {
        $innerDecoder = new StubDecoder($case['decodeKey'], $case['decodeValue']);
        $decoder = new ErrorWrappingDecoder($innerDecoder);

        $keyResult = $decoder->decodeKey('"json"');
        $valueResult = $decoder->decodeValue('"json"');

        $this->assertTrue($keyResult->isOk());
        $this->assertTrue($valueResult->isOk());
        $this->assertEquals($case['wrappedDecodeValue'], $valueResult);
        $this->assertEquals($case['wrappedDecodeKey'], $keyResult);
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

    public function testCatchesErrorInsideIteratedJsonChunk()
    {
        $json = /** @lang JSON */ '
        {
            "results": [
                {"correct": "correct"},
                {"incorrect": nulll},
                {"correct": "correct"}
            ]
        }
        ';

        $items = JsonMachine::fromString($json, '/results', new ErrorWrappingDecoder(new ExtJsonDecoder(true)));
        $result = iterator_to_array($items);

        $this->assertSame('correct', $result[0]['correct']);
        $this->assertSame('correct', $result[2]['correct']);

        /** @var DecodingError $decodingError */
        $decodingError = $result[1];
        $this->assertInstanceOf(DecodingError::class, $decodingError);
        $this->assertSame('{"incorrect":nulll}', $decodingError->getMalformedJson());
        $this->assertSame('Syntax error', $decodingError->getErrorMessage());
    }
}
