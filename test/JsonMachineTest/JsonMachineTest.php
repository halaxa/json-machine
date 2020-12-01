<?php

namespace JsonMachineTest;

use JsonMachine\JsonDecoder\PassThruDecoder;
use JsonMachine\JsonMachine;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter\PassthroughFormatter;

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

    public function dataFactories()
    {
        $extJsonResult = ['key' => 'value'];
        $passThruResult = ['key' => '"value"'];
        $ptDecoder = new PassThruDecoder();

        return [
            [$extJsonResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path'],
            [$extJsonResult, 'fromString', '{"path": {"key":"value"}}', '/path'],
            [$extJsonResult, 'fromFile', __DIR__ . '/JsonMachineTest.json', '/path'],
            [$extJsonResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path'],
            [$extJsonResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path'],

            [$passThruResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path', $ptDecoder],
            [$passThruResult, 'fromString', '{"path": {"key":"value"}}', '/path', $ptDecoder],
            [$passThruResult, 'fromFile', __DIR__ . '/JsonMachineTest.json', '/path', $ptDecoder],
            [$passThruResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path', $ptDecoder],
            [$passThruResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path', $ptDecoder],
        ];
    }

    public function testGetPosition()
    {
        $expectedPosition = ['key1' => 10, 'key2' => 20];
        $items = JsonMachine::fromString('{"key1":1, "key2":2}    ');
        foreach ($items as $key => $val) {
            $this->assertSame($expectedPosition[$key], $items->getPosition());
        }
    }
}
