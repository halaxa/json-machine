<?php

namespace JsonMachineTest;

use JsonMachine\JsonMachine;

class JsonMachineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataFactories
     */
    public function testFactories($methodName, ...$args)
    {
        $iterator = call_user_func_array(JsonMachine::class."::$methodName", $args);
        $this->assertSame(["key" => "value"], iterator_to_array($iterator));
    }

    public function dataFactories()
    {
        return [
            ['fromStream', fopen('data://text/plain,{"args": {"key":"value"}}', 'r'), '/args'],
            ['fromString', '{"args": {"key":"value"}}', '/args'],
            ['fromFile', __DIR__ . '/JsonMachineTest.json', '/args'],
        ];
    }
}
