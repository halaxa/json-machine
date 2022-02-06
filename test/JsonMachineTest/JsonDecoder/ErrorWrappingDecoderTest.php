<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\Items;
use JsonMachine\JsonDecoder\DecodingError;
use JsonMachine\JsonDecoder\ErrorWrappingDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\InvalidResult;
use JsonMachine\JsonDecoder\ValidResult;
use PHPUnit_Framework_TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\ErrorWrappingDecoder
 */
class ErrorWrappingDecoderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testCorrectlyWrapsResults
     */
    public function testCorrectlyWrapsResults(array $case)
    {
        $innerDecoder = new StubDecoder($case['result']);
        $decoder = new ErrorWrappingDecoder($innerDecoder);

        $result = $decoder->decode('"json"');

        $this->assertTrue($result->isOk());
        $this->assertEquals($case['wrappedResult'], $result);
    }

    public function data_testCorrectlyWrapsResults()
    {
        $notOkResult = new InvalidResult('Error happened.');
        $okResult = new ValidResult('json');
        $wrappedNotOkResult = new ValidResult(new DecodingError('"json"', 'Error happened.'));
        $wrappedOkResult = $okResult;

        return [
            [
                [
                    'result' => $notOkResult,
                    'wrappedResult' => $wrappedNotOkResult,
                ],
            ],
            [
                [
                    'result' => $okResult,
                    'wrappedResult' => $wrappedOkResult,
                ],
            ],
        ];
    }

    public function testCatchesErrorInsideIteratedJsonChunk()
    {
        $json = /* @lang JSON */ '
        {
            "results": [
                {"correct": "correct"},
                {"incorrect": nulll},
                {"correct": "correct"}
            ]
        }
        ';

        $items = Items::fromString($json, [
            'pointer' => '/results',
            'decoder' => new ErrorWrappingDecoder(new ExtJsonDecoder(true)),
        ]);
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
