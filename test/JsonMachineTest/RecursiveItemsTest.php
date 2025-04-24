<?php

declare(strict_types=1);

namespace JsonMachineTest;

use Iterator;
use IteratorAggregate;
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

    public function testAdvanceToKeyWorksOnScalars()
    {
        $generator = function () {yield from ['one' => 1, 'two' => 2, 'three' => 3]; };
        $iterator = new RecursiveItems(toIteratorAggregate($generator()));

        $this->assertSame(1, $iterator->advanceToKey('one'));
        $this->assertSame(1, $iterator->advanceToKey('one'));
        $this->assertSame(2, $iterator->advanceToKey('two'));
        $this->assertSame(3, $iterator->advanceToKey('three'));
    }

    public function testArrayAccessIsASyntaxSugarToAdvanceToKey()
    {
        $generator = function () {
            yield 'one' => 1;
            yield 'two' => 2;
            yield 'three' => 3;
        };
        $iterator = new RecursiveItems(toIteratorAggregate($generator()));

        $this->assertTrue(isset($iterator['two']));
        $this->assertTrue(isset($iterator['two']));

        $this->assertSame(2, $iterator['two']);
        $this->assertSame(3, $iterator['three']);

        $this->assertFalse(isset($iterator['four']));
    }

    public function testAdvanceToKeyThrows()
    {
        $generator = function () {yield from ['one' => 1, 'two' => 2, 'three' => 3]; };
        $iterator = new RecursiveItems(toIteratorAggregate($generator()));

        $this->expectExceptionMessage('not found');
        $iterator->advanceToKey('four');
    }

    public function testAdvanceToKeyCanBeChained()
    {
        $generator = function ($iterable) {yield from ['one' => 1, 'two' => 2, 'i' => $iterable, 'three' => 3]; };
        $iterator = new RecursiveItems(
            toIteratorAggregate($generator(
                toIteratorAggregate($generator(
                    toIteratorAggregate(new \ArrayIterator(['42']))
                ))
            ))
        );

        $this->assertSame(
            '42',
            $iterator
                ->advanceToKey('i')
                ->advanceToKey('i')
                ->advanceToKey(0)
        );
    }

    public function testAdvanceToKeyArraySyntaxCanBeChained()
    {
        $generator = function ($iterable) {yield from ['one' => 1, 'two' => 2, 'i' => $iterable, 'three' => 3]; };
        $iterator = new RecursiveItems(
            toIteratorAggregate($generator(
                toIteratorAggregate($generator(
                    toIteratorAggregate(new \ArrayIterator(['42']))
                ))
            ))
        );

        $this->assertSame('42', $iterator['i']['i'][0]);
    }

    public function testAdvanceToKeyArraySyntaxCanBeChainedE2E()
    {
        $iterator = RecursiveItems::fromString('[[[42]]]');

        $this->assertSame(42, $iterator[0][0][0]);
    }

    public function testToArray()
    {
        $generator = function ($iterable) {yield from ['one' => 1, 'two' => 2, 'i' => $iterable, 'three' => 3]; };
        $iterator = new RecursiveItems(
            toIteratorAggregate($generator(
                toIteratorAggregate($generator(
                    toIteratorAggregate(new \ArrayIterator(['42']))
                ))
            ))
        );

        $expected = [
            'one' => 1,
            'two' => 2,
            'i' => [
                'one' => 1,
                'two' => 2,
                'i' => ['42'],
                'three' => 3,
            ],
            'three' => 3,
        ];

        $this->assertSame($expected, $iterator->toArray());
    }

    public function testHasChildrenFollowsIterators()
    {
        $generator = function () {yield from [1, toIteratorAggregate(new \ArrayIterator([])), 3]; };
        $iterator = new RecursiveItems(toIteratorAggregate($generator()));

        $result = [];
        foreach ($iterator as $item) {
            $result[] = $iterator->hasChildren();
        }

        $this->assertSame([false, true, false], $result);
    }

    public function testToArrayThrowsMeaningfulErrorWhenIteratorIsAlreadyOpen()
    {
        $generator = function () {
            yield 'one' => 1;
            yield 'two' => 2;
            yield 'three' => 3;
        };
        $iterator = new RecursiveItems(toIteratorAggregate($generator()));

        $iterator->rewind();
        $iterator->next();

        $this->expectExceptionMessage('toArray()');
        $iterator->toArray();
    }
}

function toIteratorAggregate(Iterator $iterator): IteratorAggregate
{
    return new class($iterator) implements IteratorAggregate {
        private $iterator;

        public function __construct(Iterator $iterator)
        {
            $this->iterator = $iterator;
        }

        public function getIterator(): \Traversable
        {
            return $this->iterator;
        }
    };
}
