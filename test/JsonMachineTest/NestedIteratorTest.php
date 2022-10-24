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

    public function testGetChildrenReturnsNestedIterator()
    {
        $generator = function () {yield from [1, new \ArrayIterator([]), 3]; };
        $iterator = new NestedIterator($generator());

        $result = [];
        foreach ($iterator as $item) {
            $result[] = $iterator->getChildren();
        }

        $this->assertSame(null, $result[0]);
        $this->assertInstanceOf(NestedIterator::class, $result[1]);
        $this->assertSame(null, $result[2]);
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
}
