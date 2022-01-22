<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;
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

    /** @var ItemDecoder */
    private $jsonDecoder;

    /** @var string */
    private $matchedJsonPointer;

    /** @var array */
    private $paths;

    /** @var array */
    private $currentPath;

    /** @var array */
    private $jsonPointers;

    /** @var bool */
    private $hasSingleJsonPointer;

    /**
     * @param array|string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param ItemDecoder  $jsonDecoder
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Traversable $lexer, $jsonPointer = '', ItemDecoder $jsonDecoder = null)
    {
        $jsonPointers = (new ValidJsonPointers((array) $jsonPointer))->toArray();

        $this->lexer = $lexer;
        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder();
        $this->hasSingleJsonPointer = (count($jsonPointers) === 1);
        $this->jsonPointers = array_combine($jsonPointers, $jsonPointers);
        $this->paths = $this->buildPaths($this->jsonPointers);
    }

    private function buildPaths(array $jsonPointers): array
    {
        return array_map(function ($jsonPointer) {
            return self::jsonPointerToPath($jsonPointer);
        }, $jsonPointers);
    }

    /**
     * @return \Generator
     *
     * @throws PathNotFoundException
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        // expand token types to local variables for the fastest lookup
        foreach ($this->tokenTypes() as $firstByte => $type) {
            ${$firstByte} = $type;
        }

        $iteratorStruct = null;
        $currentPath = &$this->currentPath;
        $currentPath = [];
        $currentPathWildcard = [];
        $pointersFound = [];
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
                $jsonPointerPath = $this->getMatchingJsonPointerPath();
                $iteratorLevel = count($jsonPointerPath);
            }
            $tokenType = ${$token[0]};
            if (0 === ($tokenType & $expectedType)) {
                $this->error('Unexpected symbol', $token);
            }
            $isValue = ($tokenType | 23) === 23; // 23 = self::ANY_VALUE
            if ( ! $inObject && $isValue && $currentLevel < $iteratorLevel) {
                $currentPathChanged = ! $this->hasSingleJsonPointer;
                $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? (string) (1 + (int) $currentPath[$currentLevel]) : '0';
                $currentPathWildcard[$currentLevel] = preg_match('/^(?:\d+|-)$/S', $jsonPointerPath[$currentLevel]) ? '-' : $currentPath[$currentLevel];
                unset($currentPath[$currentLevel + 1], $currentPathWildcard[$currentLevel + 1], $stack[$currentLevel + 1]);
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
                            || ($currentLevel + 1 === $iteratorLevel && ($tokenType | 3) === 3) // 3 = self::SCALAR_VALUE
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
                            if ( ! $keyResult->isOk()) {
                                $this->error($keyResult->getErrorMessage(), $token);
                            }
                            $currentPathChanged = ! $this->hasSingleJsonPointer;
                            $currentPath[$currentLevel] = $keyResult->getValue();
                            $currentPathWildcard[$currentLevel] = $keyResult->getValue();
                            unset($keyResult, $currentPath[$currentLevel + 1], $currentPathWildcard[$currentLevel + 1]);
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
                if ( ! $valueResult->isOk()) {
                    $this->error($valueResult->getErrorMessage(), $token);
                }
                if ($iteratorStruct === '[') {
                    yield $valueResult->getValue();
                } else {
                    $keyResult = $this->jsonDecoder->decodeKey($key);
                    if ( ! $keyResult->isOk()) {
                        $this->error($keyResult->getErrorMessage(), $key);
                    }
                    yield $keyResult->getValue() => $valueResult->getValue();
                    unset($keyResult);
                }
                unset($valueResult);
            }
            if ($jsonPointerPath === $currentPath || $jsonPointerPath === $currentPathWildcard) {
                if ( ! in_array($this->matchedJsonPointer, $pointersFound, true)) {
                    $pointersFound[] = $this->matchedJsonPointer;
                }
            } elseif (count($pointersFound) === count($this->jsonPointers)) {
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

        if (count($pointersFound) !== count($this->jsonPointers)) {
            throw new PathNotFoundException(sprintf("Paths '%s' were not found in json stream.", implode(', ', array_diff($this->jsonPointers, $pointersFound))));
        }

        $this->matchedJsonPointer = null;
        $this->currentPath = null;
    }

    private function tokenTypes()
    {
        return [
            'n' => self::SCALAR_CONST,
            't' => self::SCALAR_CONST,
            'f' => self::SCALAR_CONST,
            '-' => self::SCALAR_CONST,
            '0' => self::SCALAR_CONST,
            '1' => self::SCALAR_CONST,
            '2' => self::SCALAR_CONST,
            '3' => self::SCALAR_CONST,
            '4' => self::SCALAR_CONST,
            '5' => self::SCALAR_CONST,
            '6' => self::SCALAR_CONST,
            '7' => self::SCALAR_CONST,
            '8' => self::SCALAR_CONST,
            '9' => self::SCALAR_CONST,
            '"' => self::SCALAR_STRING,
            '{' => self::OBJECT_START,
            '}' => self::OBJECT_END,
            '[' => self::ARRAY_START,
            ']' => self::ARRAY_END,
            ',' => self::COMMA,
            ':' => self::COLON,
        ];
    }

    private function getMatchingJsonPointerPath(): array
    {
        $matchingPointer = key($this->paths);

        if (count($this->paths) === 1) {
            $this->matchedJsonPointer = $matchingPointer;

            return $this->paths[$matchingPointer];
        }

        $currentPathLength = count($this->currentPath);
        $matchLength = -1;

        foreach ($this->paths as $jsonPointer => $jsonPointerPath) {
            $matchingParts = [];

            foreach ($jsonPointerPath as $i => $jsonPointerPathEl) {
                if (
                    ! isset($this->currentPath[$i])
                    || (
                        $this->currentPath[$i] !== $jsonPointerPathEl
                        && ValidJsonPointers::wildcardify($this->currentPath[$i]) !== $jsonPointerPathEl
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

        $this->matchedJsonPointer = $matchingPointer;

        return $this->paths[$matchingPointer];
    }

    /**
     * @deprecated this method was revealing internal implementation and is not useful for anything anyway
     */
    public function getJsonPointerPath()
    {
        @trigger_error(
            'This method was revealing internal implementation and is not useful for anything anyway.',
            E_USER_DEPRECATED
        );

        return reset($this->paths);
    }

    /**
     * @deprecated
     * @see Parser::getMatchedJsonPointer()
     */
    public function getJsonPointer(): string
    {
        if ( ! $this->hasSingleJsonPointer) {
            throw new JsonMachineException('Call getJsonPointers() when you provide more than one.');
        }

        return reset($this->jsonPointers);
    }

    public function getJsonPointers(): array
    {
        return array_values($this->jsonPointers);
    }

    public function getCurrentJsonPointer(): string
    {
        if ($this->currentPath === null) {
            throw new JsonMachineException('getCurrentJsonPointer() must not be called outside of a loop');
        }

        return self::pathToJsonPointer($this->currentPath);
    }

    public function getMatchedJsonPointer(): string
    {
        if ($this->matchedJsonPointer === null) {
            throw new JsonMachineException('getMatchedJsonPointer() must not be called outside of a loop');
        }

        return $this->matchedJsonPointer;
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
     *
     * @throws JsonMachineException
     */
    public function getPosition()
    {
        if ($this->lexer instanceof PositionAware) {
            return $this->lexer->getPosition();
        }

        throw new JsonMachineException('Provided lexer must implement PositionAware to call getPosition on it.');
    }

    private static function jsonPointerToPath(string $jsonPointer): array
    {
        return array_slice(array_map(function ($jsonPointerPart) {
            return str_replace(['~1', '~0'], ['/', '~'], $jsonPointerPart);
        }, explode('/', $jsonPointer)), 1);
    }

    private static function pathToJsonPointer(array $valueAddress): string
    {
        $encodedParts = array_map(function ($addressPart) {
            return str_replace(['~', '/'], ['~0', '~1'], $addressPart);
        }, $valueAddress);

        array_unshift($encodedParts, '');

        return implode('/', $encodedParts);
    }
}
