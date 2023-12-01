<?php

declare(strict_types=1);

namespace JsonMachine;

/**
 * Entry-point facade for recursive iteration.
 */
final class RecursiveItems implements \IteratorAggregate, PositionAware
{
    use FacadeTrait;

    protected function recursive(): bool
    {
        return true;
    }
}
