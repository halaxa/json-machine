<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\RecursiveItems;

/**
 * @covers \JsonMachine\RecursiveItems
 */
class RecursiveItemsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testFactories
     */
    public function testFactories($methodName, ...$args)
    {
        $iterator = call_user_func_array(RecursiveItems::class."::$methodName", [
            $args[0],
            [
                'pointer' => $args[1],
                'decoder' => $args[2],
                'debug' => $args[3],
            ],
        ]);
        $this->assertInstanceOf(RecursiveItems::class, $iterator);
    }

    public function data_testFactories()
    {
        foreach ([true, false] as $debug) {
            foreach ([
                 ['fromStream', fopen('data://text/plain,{"path": {"key":["value"]}}', 'r'), '/path', null, $debug],
                 ['fromString', '{"path": {"key":["value"]}}', '/path', null, $debug],
                 ['fromFile', __DIR__.'/RecursiveItemsTest.json', '/path', null, $debug],
                 ['fromIterable', ['{"path": {"key', '":["value"]}}'], '/path', null, $debug],
                 ['fromIterable', new \ArrayIterator(['{"path": {"key', '":["value"]}}']), '/path', null, $debug],
             ] as $case) {
                yield $case;
            }
        }
    }

    public function testRecursiveIteration()
    {
        $items = RecursiveItems::fromString('[[":)"]]');

        foreach ($items as $emojis) {
            $this->assertInstanceOf(RecursiveItems::class, $emojis);
            foreach ($emojis as $emoji) {
                $this->assertSame(':)', $emoji);
            }
        }
    }

    public function testGetChildrenReturnsNestedIterator()
    {
        $iterator = RecursiveItems::fromString('[1,[],1]');

        $result = [];
        foreach ($iterator as $item) {
            $result[] = $iterator->getChildren();
        }

        $this->assertSame(null, $result[0]);
        $this->assertInstanceOf(RecursiveItems::class, $result[1]);
        $this->assertSame(null, $result[2]);
    }

    public function testCurrentReturnsSameInstanceOfParser()
    {
    }
}
