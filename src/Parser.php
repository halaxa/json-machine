<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\ItemDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use Traversable;

class Parser implements \IteratorAggregate, PositionAware
{
    const SCALAR_CONST = 1;
    const SCALAR_STRING = 2;
    const OBJECT_START = 4;
    const OBJECT_END = 8;
    const ARRAY_START = 16;
    const ARRAY_END = 32;
    const COMMA = 64;
    const COLON = 128;
    const SCALAR_VALUE = self::SCALAR_CONST | self::SCALAR_STRING;
    const ANY_VALUE = self::OBJECT_START | self::ARRAY_START | self::SCALAR_CONST | self::SCALAR_STRING;

    const AFTER_ARRAY_START = self::ANY_VALUE | self::ARRAY_END;
    const AFTER_OBJECT_START = self::SCALAR_STRING | self::OBJECT_END;
    const AFTER_ARRAY_VALUE = self::COMMA | self::ARRAY_END;
    const AFTER_OBJECT_VALUE = self::COMMA | self::OBJECT_END;

    /** @var Traversable */
    private $lexer;

    /** @var array */
    private $jsonPointerPaths;

    /** @var array */
    private $jsonPointer;

    /** @var string */
    private $currentJsonPointer;

    /** @var ItemDecoder */
    private $jsonDecoder;

    /**
     * @param Traversable $lexer
     * @param array|string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param ItemDecoder $jsonDecoder
     * @throws InvalidArgumentException
     */
    public function __construct(Traversable $lexer, $jsonPointer = '', ItemDecoder $jsonDecoder = null)
    {
        $this->lexer = $lexer;
        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder();
        $this->buildJsonPointerPaths((array)$jsonPointer);
    }

    /**
     * @param array $jsonPointers
     * @throws InvalidArgumentException
     */
    private function buildJsonPointerPaths(array $jsonPointers)
    {
        $jsonPointers = array_values($jsonPointers);

        foreach ($jsonPointers as $jsonPointerEl) {
            if (preg_match('_^(/(([^/~])|(~[01]))*)*$_', $jsonPointerEl) === 0) {
                throw new InvalidArgumentException(sprintf("Given value '%s' of \$jsonPointer is not valid JSON Pointer", $jsonPointerEl));
            }

            $intersectingJsonPointers = array_filter($jsonPointers, static function($el) use ($jsonPointerEl) {
                if ($jsonPointerEl === $el) {
                    return false;
                }

                if (strpos($jsonPointerEl, $el) === 0) {
                    return true;
                }

                $elWildcard = preg_replace('~/\d+(/|$)~', '/-$1', $el);

                return strpos($jsonPointerEl, $elWildcard) === 0;
            });

            if (!empty($intersectingJsonPointers)) {
                throw new InvalidArgumentException(sprintf("JSON Pointers must not intersect: '%s' is within '%s'", $jsonPointerEl, current($intersectingJsonPointers)));
            }
        }

        $this->jsonPointer = array_combine($jsonPointers, $jsonPointers);
        $this->jsonPointerPaths = array_map(static function ($el) {
            return array_slice(array_map(static function ($jsonPointerPart) {
                return str_replace(['~1', '~0'], ['/', '~'], $jsonPointerPart);
            }, explode('/', $el)), 1);
        }, $this->jsonPointer);
    }

    /**
     * @param array $currentPath
     * @return array
     */
    private function getMatchingJsonPointerPath($currentPath)
    {
        $matchingPointer = key($this->jsonPointerPaths);

        if (count($this->jsonPointerPaths) === 1) {
            $this->currentJsonPointer = $matchingPointer;
            return current($this->jsonPointerPaths);
        }

        $currentPathLength = count($currentPath);
        $matchLength = -1;

        foreach ($this->jsonPointerPaths as $jsonPointer => $jsonPointerPath) {
            $matchingParts = [];

            foreach ($jsonPointerPath as $i => $jsonPointerPathEl) {
                if (
                    !isset($currentPath[$i])
                    || (
                        $currentPath[$i] !== $jsonPointerPathEl
                        && preg_replace('~/\d+(/|$)~', '/-$1', $currentPath[$i]) !== $jsonPointerPathEl
                    )
                ) {
                    continue;
                }

                $matchingParts[$i] = $jsonPointerPathEl;
            }

            if (empty($matchingParts)) {
                continue;
            }

            $currentMatchLength = count($matchingParts);

            if ($currentMatchLength > $matchLength) {
                $matchingPointer = $jsonPointer;
                $matchLength = $currentMatchLength;
            }

            if ($matchLength === $currentPathLength) {
                break;
            }
        }

        $this->currentJsonPointer = $matchingPointer;

        return $this->jsonPointerPaths[$matchingPointer];
    }

    /**
     * @return \Generator
     * @throws PathNotFoundException
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        // todo Allow to call getIterator only once per instance
        ${'n'} = self::SCALAR_CONST;
        ${'t'} = self::SCALAR_CONST;
        ${'f'} = self::SCALAR_CONST;
        ${'-'} = self::SCALAR_CONST;
        ${'0'} = self::SCALAR_CONST;
        ${'1'} = self::SCALAR_CONST;
        ${'2'} = self::SCALAR_CONST;
        ${'3'} = self::SCALAR_CONST;
        ${'4'} = self::SCALAR_CONST;
        ${'5'} = self::SCALAR_CONST;
        ${'6'} = self::SCALAR_CONST;
        ${'7'} = self::SCALAR_CONST;
        ${'8'} = self::SCALAR_CONST;
        ${'9'} = self::SCALAR_CONST;

        ${'"'} = self::SCALAR_STRING;
        ${'{'} = self::OBJECT_START;
        ${'}'} = self::OBJECT_END;
        ${'['} = self::ARRAY_START;
        ${']'} = self::ARRAY_END;
        ${','} = self::COMMA;
        ${':'} = self::COLON;

        $iteratorStruct = null;
        $currentPath = [];
        $currentPathWildcard = [];
        $pathsFound = [];
        $currentLevel = -1;
        $stack = [$currentLevel => null];
        $jsonBuffer = '';
        $key = null;
        $objectKeyExpected = false;
        $inObject = true; // hack to make "!$inObject" in first iteration work. Better code structure?
        $expectedType = self::OBJECT_START | self::ARRAY_START;
        $subtreeEnded = false;
        $token = null;
        $currentPathChanged = true;
        $jsonPointerPath = [];
        $iteratorLevel = 0;

        // local variables for faster name lookups
        $lexer = $this->lexer;

        foreach ($lexer as $token) {
            if ($currentPathChanged) {
                $currentPathChanged = false;
                $jsonPointerPath = $this->getMatchingJsonPointerPath($currentPath);
                $iteratorLevel = count($jsonPointerPath);
            }
            $tokenType = ${$token[0]};
            if (0 === ($tokenType & $expectedType)) {
                $this->error("Unexpected symbol", $token);
            }
            $isValue = ($tokenType | 23) === 23; // 23 = self::ANY_VALUE
            if (!$inObject && $isValue && $currentLevel < $iteratorLevel) {
                $currentPathChanged = true;
                $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? (string)(1+(int)$currentPath[$currentLevel]) : "0";
                $currentPathWildcard[$currentLevel] = preg_match('/^(?:\d+|-)$/', $jsonPointerPath[$currentLevel]) ? '-' : $currentPath[$currentLevel];
                unset($currentPath[$currentLevel+1], $currentPathWildcard[$currentLevel+1], $stack[$currentLevel+1]);
            }
            if (
                (
                    $jsonPointerPath === $currentPath
                    || $jsonPointerPath === $currentPathWildcard
                )
                && (
                    $currentLevel > $iteratorLevel
                    || (
                        ! $objectKeyExpected
                        && (
                            ($currentLevel === $iteratorLevel && $isValue)
                            || ($currentLevel+1 === $iteratorLevel && ($tokenType | 3) === 3) // 3 = self::SCALAR_VALUE
                        )
                    )
                )
            ) {
                $jsonBuffer .= $token;
            }
            // todo move this switch to the top just after the syntax check to be a correct FSM
            switch ($token[0]) {
                case '"':
                    if ($objectKeyExpected) {
                        $objectKeyExpected = false;
                        $expectedType = 128; // 128 = self::COLON
                        if ($currentLevel === $iteratorLevel) {
                            $key = $token;
                        } elseif ($currentLevel < $iteratorLevel) {
                            $key = $token;
                            $keyResult = $this->jsonDecoder->decodeInternalKey($token);
                            if (! $keyResult->isOk()) {
                                $this->error($keyResult->getErrorMessage(), $token);
                            }
                            $currentPathChanged = true;
                            $currentPath[$currentLevel] = $keyResult->getValue();
                            $currentPathWildcard[$currentLevel] = $keyResult->getValue();
                            unset($keyResult, $currentPath[$currentLevel+1], $currentPathWildcard[$currentLevel+1]);
                        }
                        continue 2; // a valid json chunk is not completed yet
                    }
                    if ($inObject) {
                        $expectedType = 72; // 72 = self::AFTER_OBJECT_VALUE;
                    } else {
                        $expectedType = 96; // 96 = self::AFTER_ARRAY_VALUE;
                    }
                    break;
                case ',':
                    if ($inObject) {
                        $objectKeyExpected = true;
                        $expectedType = 2; // 2 = self::SCALAR_STRING
                    } else {
                        $expectedType = 23; // 23 = self::ANY_VALUE
                    }
                    continue 2; // a valid json chunk is not completed yet
                case ':':
                    $expectedType = 23; // 23 = self::ANY_VALUE
                    continue 2; // a valid json chunk is not completed yet
                case '{':
                    ++$currentLevel;
                    if ($currentLevel <= $iteratorLevel) {
                        $iteratorStruct = '{';
                    }
                    $stack[$currentLevel] = '{';
                    $inObject = true;
                    $expectedType = 10; // 10 = self::AFTER_OBJECT_START
                    $objectKeyExpected = true;
                    continue 2; // a valid json chunk is not completed yet
                case '[':
                    ++$currentLevel;
                    if ($currentLevel <= $iteratorLevel) {
                        $iteratorStruct = '[';
                    }
                    $stack[$currentLevel] = '[';
                    $inObject = false;
                    $expectedType = 55; // 55 = self::AFTER_ARRAY_START;
                    continue 2; // a valid json chunk is not completed yet
                case '}':
                    $objectKeyExpected = false;
                    // no break
                case ']':
                    --$currentLevel;
                    $inObject = $stack[$currentLevel] === '{';
                    // no break
                default:
                    if ($inObject) {
                        $expectedType = 72; // 72 = self::AFTER_OBJECT_VALUE;
                    } else {
                        $expectedType = 96; // 96 = self::AFTER_ARRAY_VALUE;
                    }
            }
            if ($currentLevel > $iteratorLevel) {
                continue; // a valid json chunk is not completed yet
            }
            if ($jsonBuffer !== '') {
                $valueResult = $this->jsonDecoder->decodeValue($jsonBuffer);
                $jsonBuffer = '';
                if (! $valueResult->isOk()) {
                    $this->error($valueResult->getErrorMessage(), $token);
                }
                if ($iteratorStruct === '[') {
                    yield $valueResult->getValue();
                } else {
                    $keyResult = $this->jsonDecoder->decodeKey($key);
                    if (! $keyResult->isOk()) {
                        $this->error($keyResult->getErrorMessage(), $key);
                    }
                    yield $keyResult->getValue() => $valueResult->getValue();
                    unset($keyResult);
                }
                unset($valueResult);
            }
            if ($jsonPointerPath === $currentPath || $jsonPointerPath === $currentPathWildcard) {
                if (!in_array($this->currentJsonPointer, $pathsFound, true)) {
                    $pathsFound[] = $this->currentJsonPointer;
                }
            }
            elseif (count($pathsFound) === count($this->jsonPointerPaths)) {
                $subtreeEnded = true;
                break;
            }
        }

        if ($token === null) {
            $this->error('Cannot iterate empty JSON', $token);
        }

        if ($currentLevel > -1 && ! $subtreeEnded) {
            $this->error('JSON string ended unexpectedly', $token, UnexpectedEndSyntaxErrorException::class);
        }

        if (count($pathsFound) !== count($this->jsonPointerPaths)) {
            throw new PathNotFoundException(sprintf("Paths '%s' were not found in json stream.", implode(', ', array_diff($this->jsonPointer, $pathsFound))));
        }
    }

    /**
     * @return array
     */
    public function getJsonPointerPath()
    {
        return $this->jsonPointerPaths;
    }

    /**
     * @return array
     */
    public function getJsonPointer()
    {
        return $this->jsonPointer;
    }

    /**
     * @return string
     */
    public function getMatchedJsonPointer()
    {
        return !empty($this->currentJsonPointer) ? $this->currentJsonPointer : '';
    }

    /**
     * @param string $msg
     * @param string $token
     * @param string $exception
     */
    private function error($msg, $token, $exception = SyntaxError::class)
    {
        throw new $exception($msg." '".$token."'", $this->lexer->getPosition());
    }

    /**
     * @return int
     * @throws JsonMachineException
     */
    public function getPosition()
    {
        if ($this->lexer instanceof PositionAware) {
            return $this->lexer->getPosition();
        }

        throw new JsonMachineException('Provided lexer must implement PositionAware to call getPosition on it.');
    }
}
