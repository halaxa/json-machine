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
                    $exceptionMessage = "Option '$optionName' does not exist.";
                    $suggestion = self::getSuggestion(array_keys(self::defaultOptions()), $optionName);
                    if ($suggestion) {
                        $exceptionMessage .= " Did you mean '$suggestion'?";
                    }
                    throw new InvalidArgumentException($exceptionMessage);
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

    private function opt_decoder(?ItemDecoder $decoder = null)
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

    /**
     * From Nette ObjectHelpers.
     *
     * @see https://github.com/nette/utils/blob/master/src/Utils/ObjectHelpers.php
     *
     * Finds the best suggestion (for 8-bit encoding).
     *
     * @param  (\ReflectionFunctionAbstract|\ReflectionParameter|\ReflectionClass|\ReflectionProperty|string)[]  $possibilities
     *
     * @internal
     */
    private static function getSuggestion(array $possibilities, string $value): ?string
    {
        $norm = preg_replace($re = '#^(get|set|has|is|add)(?=[A-Z])#', '+', $value);
        $best = null;
        $min = (strlen($value) / 4 + 1) * 10 + .1;
        foreach (array_unique($possibilities, SORT_REGULAR) as $item) {
            $item = $item instanceof \Reflector ? $item->name : $item;
            if ($item !== $value && (
                ($len = levenshtein($item, $value, 10, 11, 10)) < $min
                || ($len = levenshtein(preg_replace($re, '*', $item), $norm, 10, 11, 10)) < $min
            )) {
                $min = $len;
                $best = $item;
            }
        }

        return $best;
    }
}
