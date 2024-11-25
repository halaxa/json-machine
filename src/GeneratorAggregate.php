<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * @template TKey
 * @template TValue
 */
interface GeneratorAggregate extends \IteratorAggregate
{
    /**
     * @return \Generator<TKey, TValue>
     */
    public function getIterator(): \Generator;
}
