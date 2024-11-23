<?php

declare(strict_types=1);

namespace JsonMachine\JsonDecoder;

class StringOnlyDecoder implements ItemDecoder
{
    /** @var ItemDecoder */
    private $innerDecoder;

    /**
     * @var self
     */
    private static $instance;

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

    public static function instance(ItemDecoder $innerDecoder): self
    {
        if ( ! self::$instance) {
            self::$instance = new self($innerDecoder);
        }

        return self::$instance;
    }
}
