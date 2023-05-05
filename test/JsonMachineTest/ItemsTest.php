<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\Items;
use JsonMachine\JsonDecoder\PassThruDecoder;

/**
 * @covers \JsonMachine\Items
 */
class ItemsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider data_testFactories
     */
    public function testFactories($expected, $methodName, ...$args)
    {
        $iterator = call_user_func_array(Items::class."::$methodName", [
            $args[0],
            [
                'pointer' => $args[1],
                'decoder' => $args[2],
                'debug' => $args[3],
            ],
        ]);
        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testItemsYieldsObjectItemsByDefault()
    {
        $iterator = Items::fromString('{"path": {"key":"value"}}');
        foreach ($iterator as $item) {
            $this->assertEquals((object) ['key' => 'value'], $item);
        }
    }

    public function data_testFactories()
    {
        $extJsonResult = ['key' => 'value'];
        $passThruResult = ['"key"' => '"value"'];
        $ptDecoder = new PassThruDecoder();

        foreach ([true, false] as $debug) {
            foreach ([
                [$extJsonResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path', null, $debug],
                [$extJsonResult, 'fromString', '{"path": {"key":"value"}}', '/path', null, $debug],
                [$extJsonResult, 'fromFile', __DIR__.'/ItemsTest.json', '/path', null, $debug],
                [$extJsonResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path', null, $debug],
                [$extJsonResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path', null, $debug],

                [$passThruResult, 'fromStream', fopen('data://text/plain,{"path": {"key":"value"}}', 'r'), '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromString', '{"path": {"key":"value"}}', '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromFile', __DIR__.'/ItemsTest.json', '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromIterable', ['{"path": {"key', '":"value"}}'], '/path', $ptDecoder, $debug],
                [$passThruResult, 'fromIterable', new \ArrayIterator(['{"path": {"key', '":"value"}}']), '/path', $ptDecoder, $debug],
            ] as $case) {
                yield $case;
            }
        }
    }

    public function testGetPositionDebugEnabled()
    {
        $expectedPosition = ['key1' => 9, 'key2' => 19];
        $items = Items::fromString('{"key1":1, "key2":2}    ', ['debug' => true]);
        foreach ($items as $key => $val) {
            $this->assertSame($expectedPosition[$key], $items->getPosition());
        }
    }

    public function testIterationWithoutForeach()
    {
        $iterator =
            Items::fromString('{"key1":1, "key2":2}')
            ->getIterator();

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(['key1', 1], [$iterator->key(), $iterator->current()]);
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(['key2', 2], [$iterator->key(), $iterator->current()]);
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    public function testIsDebugEnabled()
    {
        $items = $iterator = Items::fromString('{}');
        $this->assertFalse($items->isDebugEnabled());

        $items = $iterator = Items::fromString('{}', ['debug' => true]);
        $this->assertTrue($items->isDebugEnabled());
    }

    public function testGetCurrentJsonPointer()
    {
        $items = $iterator = Items::fromString(
            '[{"two": 2, "one": 1}]',
            ['pointer' => ['/-/one', '/-/two']]
        );
        $iterator = $items->getIterator();

        $iterator->rewind();
        $iterator->current();

        $this->assertSame('/0/two', $items->getCurrentJsonPointer());

        $iterator->next();
        $iterator->current();

        $this->assertSame('/0/one', $items->getCurrentJsonPointer());
    }

    public function testGetMatchedJsonPointer()
    {
        $items = $iterator = Items::fromString(
            '[{"two": 2, "one": 1}]',
            ['pointer' => ['/-/one', '/-/two']]
        );
        $iterator = $items->getIterator();

        $iterator->rewind();
        $iterator->current();

        $this->assertSame('/-/two', $items->getMatchedJsonPointer());

        $iterator->next();
        $iterator->current();

        $this->assertSame('/-/one', $items->getMatchedJsonPointer());
    }

    public function testGetJsonPointers()
    {
        $items = Items::fromString('[]', ['pointer' => ['/one', '/two']]);

        $this->assertSame(['/one', '/two'], $items->getJsonPointers());
    }

    public function testCountViaIteratorCount()
    {
        $items = Items::fromIterable(['{"results":', '[1,2,3]}'], ['pointer' => ['/results']]);

        $this->assertSame(3, iterator_count($items));
    }
}
