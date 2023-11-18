<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;

class ItemsOptions extends \ArrayObject
{
    private $options = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        $this->validateOptions($options);

        parent::__construct($this->options);
    }

    public function toArray(): array
    {
        return $this->options;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateOptions(array $options)
    {
        $mergedOptions = array_merge(self::defaultOptions(), $options);

        try {
            foreach ($mergedOptions as $optionName => $optionValue) {
                if ( ! isset(self::defaultOptions()[$optionName])) {
                    throw new InvalidArgumentException("Option '$optionName' does not exist.");
                }
                $this->options[$optionName] = $this->{"opt_$optionName"}($optionValue);
            }
        } catch (\TypeError $typeError) {
            throw new InvalidArgumentException(
                preg_replace('~Argument #[0-9]+~', "Option '$optionName'", $typeError->getMessage())
            );
        }
    }

    private function opt_pointer($pointer)
    {
        if (is_array($pointer)) {
            (function (string ...$p) {})(...$pointer);
        } else {
            (function (string $p) {})($pointer);
        }

        return $pointer;
    }

    private function opt_decoder(ItemDecoder $decoder = null)
    {
        return $decoder;
    }

    private function opt_debug(bool $debug)
    {
        return $debug;
    }

    public static function defaultOptions(): array
    {
        return [
            'pointer' => '',
            'decoder' => new ExtJsonDecoder(),
            'debug' => false,
        ];
    }
}
