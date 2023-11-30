<?php

declare(strict_types=1);

namespace JsonMachineTest\JsonDecoder;

use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\StringOnlyDecoder;
use JsonMachine\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\JsonDecoder\StringOnlyDecoder
 */
class StringOnlyDecoderTest extends TestCase
{
    public function testPassesValueToInnerDecoder()
    {
        $innerDecoder = new ExtJsonDecoder();
        $decoder = new StringOnlyDecoder($innerDecoder);

        $this->assertSame('value', $decoder->decode('"value"')->getValue());
    }

    public function testDoesNotPassParserIntoInnerDecoder()
    {
        $innerDecoder = new ExtJsonDecoder();
        $decoder = new StringOnlyDecoder($innerDecoder);
        $parser = new Parser(new \ArrayObject(['[]']));

        $this->assertSame($parser, $decoder->decode($parser)->getValue());
    }
}
