<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\NestedIterator;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;

/**
 * @covers \JsonMachine\NestedIterator
 */
class NestedIteratorTest extends TestCase
{
    public function testIteratesOverPassedIterator()
    {
        $generator = function () {yield from [1, 2, 3]; };
        $iterator = new NestedIterator($generator());

        $result = iterator_to_array($iterator);

        $this->assertSame([1, 2, 3], $result);
    }

    public function testHasChildrenIgnoresArrays()
    {
        $generator = function () {yield from [1, [2], 3]; };
        $iterator = new NestedIterator($generator());

        foreach ($iterator as $item) {
            $this->assertFalse($iterator->hasChildren());
        }
    }

    public function testHasChildrenFollowsIterators()
    {
        $generator = function () {yield from [1, new \ArrayIterator([]), 3]; };
        $iterator = new NestedIterator($generator());

        $result = [];
        foreach ($iterator as $item) {
            $result[] = $iterator->hasChildren();
        }

        $this->assertSame([false, true, false], $result);
    }

    public function testGetChildrenReturnsCorrectItems()
    {
        $generator = function () {yield from [1, new \ArrayIterator([2]), 3]; };
        $iterator = new RecursiveIteratorIterator(
            new NestedIterator($generator())
        );

        $result = iterator_to_array($iterator, false);

        $this->assertSame([1, 2, 3], $result);
    }

    public function testAdvanceToKeyWorks()
    {
        $generator = function () {yield from ['one' => 1, 'two' => 2, 'three' => 3]; };
        $iterator = new NestedIterator($generator());

        $this->assertSame(1, $iterator->advanceToKey('one'));
        $this->assertSame(1, $iterator->advanceToKey('one'));
        $this->assertSame(2, $iterator->advanceToKey('two'));
        $this->assertSame(3, $iterator->advanceToKey('three'));
    }

    public function testAdvanceToKeyThrows()
    {
        $generator = function () {yield from ['one' => 1, 'two' => 2, 'three' => 3]; };
        $iterator = new NestedIterator($generator());

        $this->expectExceptionMessage('not found');
        $iterator->advanceToKey('four');
    }

    public function testToArray()
    {
        $generator = function ($iterable) {yield from ['one' => 1, 'two' => 2, 'i' => $iterable, 'three' => 3]; };
        $iterator = new NestedIterator($generator($generator(['42'])));

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
}
