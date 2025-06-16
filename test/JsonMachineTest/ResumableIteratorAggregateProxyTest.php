<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\ResumableIteratorAggregateProxy;

/**
 * @covers \JsonMachine\ResumableIteratorAggregateProxy
 */
class ResumableIteratorAggregateProxyTest extends \PHPUnit_Framework_TestCase
{
    public function testDoesNotPassTheCallToRewindToInnerIterator()
    {
        $iteratorAggregate = new class() implements \IteratorAggregate {
            private $generator;

            public function getIterator(): \Generator
            {
                if ( ! $this->generator) {
                    $this->generator = (function () {
                        yield 1;
                        yield 2;
                    })();
                }

                return $this->generator;
            }
        };

        $this->assertSame(1, $iteratorAggregate->getIterator()->current());

        $iteratorAggregate->getIterator()->next();

        foreach (new ResumableIteratorAggregateProxy($iteratorAggregate) as $value) {
            $this->assertSame(2, $value);
        }
    }
}
