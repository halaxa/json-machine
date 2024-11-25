<?php declare(strict_types=1);


namespace JsonMachine;

use JsonMachine\GeneratorAggregate;

class GeneratorAggregateWrapper implements GeneratorAggregate
{
    /**
     * @var iterable
     */
    private $iterable;

    public function __construct(iterable $iterable)
    {
        $this->iterable = $iterable;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->iterable as $key => $value) {
            yield $key => $value;
        }
    }
}
