<?php

declare(strict_types=1);

namespace JsonMachine;

use IteratorAggregate;

/**
 * Allows to resume iteration of the inner IteratorAggregate via foreach, which would be otherwise impossible as
 * foreach implicitly calls reset(). This Iterator does not pass the reset() call to the inner Iterator thus enabling
 * to follow up on a previous iteation.
 */
class ResumableTokensProxy implements IteratorAggregate, PositionAware
{
    /** @var \Iterator */
    private $generator;

    /** @var \Traversable|PositionAware */
    private $tokens;

    public function __construct(\Traversable $tokens, \Iterator $tokensGenerator)
    {
        $this->generator = $tokensGenerator;
        $this->tokens = $tokens;
    }

    public function getIterator(): \Traversable
    {
        $generator = $this->generator;
        while ($generator->valid()) {
            yield $generator->key() => $generator->current();
            $generator->next();
        }
    }

    public function __call($name, $arguments)
    {
        return $this->generator->$name(...$arguments);
    }

    /**
     * Returns JSON bytes read so far.
     */
    public function getPosition()
    {
        return $this->tokens->getPosition();
    }
}
