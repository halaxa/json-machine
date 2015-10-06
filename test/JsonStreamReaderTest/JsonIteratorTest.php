<?php

namespace JsonIteratorTest;

use JsonIterator\JsonIterator;

class JsonIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataFactories
     */
    public function testFactories($methodName, ...$args)
    {
        $iterator = call_user_func_array(JsonIterator::class."::$methodName", $args);
        $this->assertSame(["key" => "value"], iterator_to_array($iterator));
    }

    public function dataFactories()
    {
        return [
            ['fromStream', fopen('data://text/plain,{"args": {"key":"value"}}', 'r'), '/args'],
            ['fromString', '{"args": {"key":"value"}}', '/args'],
            ['fromFile', __DIR__ . '/JsonIteratorTest.json', '/args'],
        ];
    }
}
