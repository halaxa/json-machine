<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

use JsonMachine\Parser;

class StringOnlyDecoder implements ItemDecoder
{
    /** @var ItemDecoder */
    private $innerDecoder;

    public function __construct(ItemDecoder $innerDecoder)
    {
        $this->innerDecoder = $innerDecoder;
    }

    public function decode($jsonValue)
    {
        if (is_string($jsonValue)) {
            return $this->innerDecoder->decode($jsonValue);
        }

        return new ValidResult($jsonValue);
    }
}
