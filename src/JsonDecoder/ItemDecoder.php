<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

interface ItemDecoder
{
    /**
     * Decodes composite or scalar JSON values which are directly yielded to the user.
     *
     * @return InvalidResult|ValidResult
     */
    public function decode($jsonValue);
}
