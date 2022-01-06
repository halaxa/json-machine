<?php

namespace JsonMachineTest;

use JsonMachine\JsonDecoder\ItemDecoder;
use JsonMachine\JsonDecoder\Decoder;
use JsonMachine\JsonDecoder\DecodingResult;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\PassThruDecoder;
use JsonMachine\JsonMachine;
use JsonMachine\Lexer;
use JsonMachine\Parser;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter\PassthroughFormatter;

/**
 * @deprecated
 */
class JsonMachineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataFactories
     */
    public function testFactories($expected, $methodName, ...$args)
    {
        $iterator = call_user_func_array(JsonMachine::class."::$methodName", $args);
        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testJsonMachineYieldsArraysByDefault()
    {
        $iterator = JsonMachine::fromString('{"path": {"key":"value"}}');
        foreach ($iterator as $item) {
            $this->assertEquals(['key' => 'value'], $item);
        }
    }

    public function dataFactories()
    {
        $extJsonResult = ['key' => 'value'];
        $passThruResult = ['key' => '"value"'];
        $ptDecoder = new PassThruDecoder();

        foreach ([true, false] as $debug) {
            foreach ([
                [$extJsonResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path', null, $debug],
                [$extJsonResult, 'fromString', '{"path": {"key":"value"}}', '/path', null, $debug],
                [$extJsonResult, 'fromFile', __DIR__ . '/JsonMachineTest.json', '/path', null, $debug],
                [$extJsonResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path', null, $debug],
                [$extJsonResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path', null, $debug],

                [$passThruResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromString', '{"path": {"key":"value"}}', '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromFile', __DIR__ . '/JsonMachineTest.json', '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path', $ptDecoder, $debug],
            ] as $case) {
                yield $case;
            }
        }
    }


    public function testIterationWithoutForeach()
    {
        $iterator =
            JsonMachine::fromString('{"key1":1, "key2":2}')
            ->getIterator()->getIterator();

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(['key1', 1], [$iterator->key(), $iterator->current()]);
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(['key2', 2], [$iterator->key(), $iterator->current()]);
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    public function testParserSupportsOldDecoderInterface()
    {
        $parser = new Parser(new Lexer(['{"key": "value"}']), "", new DeprecatedDecoderImpl());

        foreach ($parser as $key => $value) {
            $this->assertSame('key', $key);
            $this->assertSame('value', $value);
        }
    }
}

class DeprecatedDecoderImpl implements Decoder
{
    /**
     * @var ItemDecoder
     */
    private $decoder;

    public function __construct()
    {
        $this->decoder = new ExtJsonDecoder(true);
    }

    public function decodeKey($jsonScalarKey)
    {
        return $this->decoder->decodeKey($jsonScalarKey);
    }

    public function decodeValue($jsonValue)
    {
        return $this->decoder->decodeValue($jsonValue);
    }
}
