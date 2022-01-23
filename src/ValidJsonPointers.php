<?php

declare(strict_types=1);

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;

final class ValidJsonPointers
{
    private $jsonPointers = [];

    private $validated = false;

    public function __construct(array $jsonPointers)
    {
        $this->jsonPointers = array_values($jsonPointers);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function toArray(): array
    {
        if ( ! $this->validated) {
            $this->validate();
        }

        return $this->jsonPointers;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate()
    {
        $this->validateFormat();
        $this->validateJsonPointersDoNotIntersect();
        $this->validated = true;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateFormat()
    {
        foreach ($this->jsonPointers as $jsonPointerEl) {
            if (preg_match('_^(/(([^/~])|(~[01]))*)*$_', $jsonPointerEl) === 0) {
                throw new InvalidArgumentException(
                    sprintf("Given value '%s' of \$jsonPointer is not valid JSON Pointer", $jsonPointerEl)
                );
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateJsonPointersDoNotIntersect()
    {
        foreach ($this->jsonPointers as $keyA => $jsonPointerA) {
            foreach ($this->jsonPointers as $keyB => $jsonPointerB) {
                if ($keyA === $keyB) {
                    continue;
                }
                if ($jsonPointerA === $jsonPointerB
                    || self::str_contains($jsonPointerA, $jsonPointerB)
                    || self::str_contains($jsonPointerA, self::wildcardify($jsonPointerB))
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "JSON Pointers must not intersect. At least these two do: '%s', '%s'",
                            $jsonPointerA,
                            $jsonPointerB
                        )
                    );
                }
            }
        }
    }

    public static function wildcardify(string $jsonPointerPart): string
    {
        return preg_replace('~/\d+(/|$)~S', '/-$1', $jsonPointerPart);
    }

    /**
     * @see https://github.com/symfony/polyfill/blob/v1.24.0/src/Php80/Php80.php
     */
    public static function str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
