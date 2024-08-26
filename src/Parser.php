<?php

declare(strict_types=1);

namespace JsonMachine;

use Generator;
use Iterator;
use IteratorAggregate;
use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxErrorException;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\ItemDecoder;
use JsonMachine\JsonDecoder\StringOnlyDecoder;
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
    private $tokens;

    /** @var Iterator<int, string> */
    private $tokensIterator;

    /** @var ItemDecoder */
    private $jsonDecoder;

    /** @var string|null */
    private $matchedJsonPointer;

    /** @var array */
    private $paths;

    /** @var array|null */
    private $currentPath;

    /** @var array */
    private $jsonPointers;

    /** @var bool */
    private $hasSingleJsonPointer;

    /** @var bool */
    private $recursive;

    /**
     * @param array|string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Traversable $tokens, $jsonPointer = '', ItemDecoder $jsonDecoder = null, $recursive = false)
    {
        $jsonPointers = (new ValidJsonPointers((array) $jsonPointer))->toArray();

        $this->tokens = $tokens;
        if ($tokens instanceof IteratorAggregate) {
            $this->tokensIterator = $tokens->getIterator();
        } elseif ($tokens instanceof Iterator) {
            $this->tokensIterator = $tokens;
        } else {
            throw new InvalidArgumentException('$tokens must be either an instance of Iterator or IteratorAggregate.');
        }

        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder();
        if ($recursive) {
            $this->jsonDecoder = new StringOnlyDecoder($this->jsonDecoder);
        }
        $this->hasSingleJsonPointer = (count($jsonPointers) === 1);
        $this->jsonPointers = array_combine($jsonPointers, $jsonPointers);
        $this->paths = $this->buildPaths($this->jsonPointers);
        $this->recursive = $recursive;
    }

    private function buildPaths(array $jsonPointers): array
    {
        return array_map(function ($jsonPointer) {
            return self::jsonPointerToPath($jsonPointer);
        }, $jsonPointers);
    }

    /**
     * @return Generator
     *
     * @throws PathNotFoundException
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $tokenTypes = $this->tokenTypes();

        $iteratorStruct = null;
        $currentPath = &$this->currentPath;
        $currentPath = [];
        $currentPathWildcard = [];
        $pointersFound = [];
        $currentLevel = -1;
        $stack = [$currentLevel => null];
        $jsonValue = '';
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
        $tokens = $this->tokensIterator;

        foreach ($tokens as $token) {
            if ($currentPathChanged) {
                $currentPathChanged = false;
                $jsonPointerPath = $this->getMatchingJsonPointerPath();
                $iteratorLevel = count($jsonPointerPath);
            }
            $tokenType = $tokenTypes[$token[0]];
            if (0 == ($tokenType & $expectedType)) {
                $this->error('Unexpected symbol', $token);
            }
            $isValue = ($tokenType | 23) == 23; // 23 = self::ANY_VALUE
            if ( ! $inObject && $isValue && $currentLevel < $iteratorLevel) {
                $currentPathChanged = ! $this->hasSingleJsonPointer;
                $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? $currentPath[$currentLevel] + 1 : 0;
                $currentPathWildcard[$currentLevel] = preg_match('/^(?:\d+|-)$/S', $jsonPointerPath[$currentLevel]) ? '-' : $currentPath[$currentLevel];
                array_splice($currentPath, $currentLevel + 1);
                array_splice($currentPathWildcard, $currentLevel + 1);
            }
            if (
                (   // array_diff may be replaced with '==' when PHP 7 stops being supported
                    ! array_diff($jsonPointerPath, $currentPath)
                    || ! array_diff($jsonPointerPath, $currentPathWildcard)
                )
                && (
                    $currentLevel > $iteratorLevel
                    || (
                        ! $objectKeyExpected
                        && (
                            ($currentLevel == $iteratorLevel && $isValue)
                            || ($currentLevel + 1 == $iteratorLevel && ($tokenType | 3) == 3) // 3 = self::SCALAR_VALUE
                        )
                    )
                )
            ) {
                if ($this->recursive && ($token == '{' || $token == '[')) {
                    $jsonValue = (new self(
                        $this->remainingTokens(),
                        '',
                        $this->jsonDecoder,
                        true
                    ))->$this->getIterator();
                    $token = ' ';
                } else {
                    $jsonValue .= $token;
                }
            }
            // todo move this switch to the top just after the syntax check to be a correct FSM
            switch ($token[0]) {
                case '"':
                    if ($objectKeyExpected) {
                        $objectKeyExpected = false;
                        $expectedType = 128; // 128 = self::COLON
                        if ($currentLevel == $iteratorLevel) {
                            $key = $token;
                        } elseif ($currentLevel < $iteratorLevel) {
                            $key = $token;
                            $referenceToken = substr($token, 1, -1);
                            $currentPathChanged = ! $this->hasSingleJsonPointer;
                            $currentPath[$currentLevel] = $referenceToken;
                            $currentPathWildcard[$currentLevel] = $referenceToken;
                            array_splice($currentPath, $currentLevel + 1);
                            array_splice($currentPathWildcard, $currentLevel + 1);
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
                    $inObject = $stack[$currentLevel] == '{';
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
            if ($jsonValue !== '') {
                $valueResult = $this->jsonDecoder->decode($jsonValue);
                $jsonValue = '';
                if ( ! $valueResult->isOk()) {
                    $this->error($valueResult->getErrorMessage(), $token);
                }
                $value = $valueResult->getValue();
                if ($iteratorStruct == '[') {
                    yield $value;
                } else {
                    $keyResult = $this->jsonDecoder->decode($key);
                    if ( ! $keyResult->isOk()) {
                        $this->error($keyResult->getErrorMessage(), $key);
                    }
                    yield $keyResult->getValue() => $value;
                    unset($keyResult);
                }
                if ($value instanceof Iterator && $value->valid()) {
                    $this->eatAllRemainingTokens($value);
                }
                unset($valueResult);
                unset($value);
            }
            if (
                ! array_diff($jsonPointerPath, $currentPath)
                || ! array_diff($jsonPointerPath, $currentPathWildcard)
            ) {
                if ( ! in_array($this->matchedJsonPointer, $pointersFound, true)) {
                    $pointersFound[] = $this->matchedJsonPointer;
                }
            } elseif (count($pointersFound) == count($this->jsonPointers) && ! $this->inJsonPointer()) {
                $subtreeEnded = true;
                break;
            }
            if ($currentLevel < 0) {
                break;
            }
        }

        if ($token === null) {
            $this->error('Cannot iterate empty JSON', '');
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

    /**
     * @return Generator
     */
    private function remainingTokens()
    {
        $iterator = $this->tokensIterator;
        while ($iterator->valid()) {
            yield $iterator->current();
            $iterator->next();
        }
    }

    private function eatAllRemainingTokens(Iterator $iterator)
    {
        foreach ($iterator as $item) {
            if ($item instanceof Iterator) {
                $this->eatAllRemainingTokens($item);
            }
        }
    }

    private function tokenTypes()
    {
        $allBytes = [];

        foreach (range(0, 255) as $ord) {
            $allBytes[chr($ord)] = 0;
        }

        $allBytes['n'] = self::SCALAR_CONST;
        $allBytes['t'] = self::SCALAR_CONST;
        $allBytes['f'] = self::SCALAR_CONST;
        $allBytes['-'] = self::SCALAR_CONST;
        $allBytes['0'] = self::SCALAR_CONST;
        $allBytes['1'] = self::SCALAR_CONST;
        $allBytes['2'] = self::SCALAR_CONST;
        $allBytes['3'] = self::SCALAR_CONST;
        $allBytes['4'] = self::SCALAR_CONST;
        $allBytes['5'] = self::SCALAR_CONST;
        $allBytes['6'] = self::SCALAR_CONST;
        $allBytes['7'] = self::SCALAR_CONST;
        $allBytes['8'] = self::SCALAR_CONST;
        $allBytes['9'] = self::SCALAR_CONST;
        $allBytes['"'] = self::SCALAR_STRING;
        $allBytes['{'] = self::OBJECT_START;
        $allBytes['}'] = self::OBJECT_END;
        $allBytes['['] = self::ARRAY_START;
        $allBytes[']'] = self::ARRAY_END;
        $allBytes[','] = self::COMMA;
        $allBytes[':'] = self::COLON;

        return $allBytes;
    }

    private function getMatchingJsonPointerPath(): array
    {
        $matchingPointerByIndex = [];

        foreach ($this->paths as $jsonPointer => $referenceTokens) {
            foreach ($this->currentPath as $index => $pathToken) {
                if ( ! isset($referenceTokens[$index]) || ! $this->pathMatchesPointer($pathToken, $referenceTokens[$index])) {
                    continue 2;
                } elseif ( ! isset($matchingPointerByIndex[$index])) {
                    $matchingPointerByIndex[$index] = $jsonPointer;
                }
            }
        }

        $matchingPointer = end($matchingPointerByIndex) ?: key($this->paths);

        $this->matchedJsonPointer = $matchingPointer;

        return $this->paths[$matchingPointer];
    }

    public function getJsonPointers(): array
    {
        return array_values($this->jsonPointers);
    }

    /**
     * @throws JsonMachineException
     */
    public function getCurrentJsonPointer(): string
    {
        if ($this->currentPath === null) {
            throw new JsonMachineException(__METHOD__.' must be called inside a loop');
        }

        return self::pathToJsonPointer($this->currentPath);
    }

    /**
     * @throws JsonMachineException
     */
    public function getMatchedJsonPointer(): string
    {
        if ($this->isOutsideGenerator()) {
            throw new JsonMachineException(__METHOD__.' must be called inside a loop');
        }

        return $this->matchedJsonPointer;
    }

    /**
     * @param string $msg
     * @param string $token
     * @param string $exception
     */
    private function error($msg, $token, $exception = SyntaxErrorException::class)
    {
        throw new $exception(
            $msg." '".$token."'",
            $this->tokens instanceof PositionAware ? $this->tokens->getPosition() : ''
        );
    }

    /**
     * @return int
     *
     * @throws JsonMachineException
     */
    public function getPosition()
    {
        if ($this->tokens instanceof PositionAware) {
            return $this->tokens->getPosition();
        }

        throw new JsonMachineException('Provided tokens iterable must implement PositionAware to call getPosition on it.');
    }

    private static function jsonPointerToPath(string $jsonPointer): array
    {
        return array_slice(array_map(function ($jsonPointerPart) {
            return str_replace(['~1', '~0'], ['/', '~'], $jsonPointerPart);
        }, explode('/', $jsonPointer)), 1);
    }

    private static function pathToJsonPointer(array $path): string
    {
        $encodedParts = array_map(function ($addressPart) {
            return str_replace(['~', '/'], ['~0', '~1'], (string) $addressPart);
        }, $path);

        array_unshift($encodedParts, '');

        return implode('/', $encodedParts);
    }

    /**
     * Determine whether the current position is within one of the JSON pointers.
     */
    private function inJsonPointer(): bool
    {
        $jsonPointerPath = $this->paths[$this->matchedJsonPointer];

        if (($firstNest = array_search('-', $jsonPointerPath)) === false) {
            return false;
        }

        return array_slice($jsonPointerPath, 0, $firstNest) == array_slice($this->currentPath, 0, $firstNest);
    }

    /**
     * Determine whether the given path reference token matches the provided JSON pointer reference token.
     *
     * @param string|int $pathToken
     */
    private function pathMatchesPointer($pathToken, string $pointerToken): bool
    {
        if ($pointerToken === (string) $pathToken) {
            return true;
        }

        return is_int($pathToken) && $pointerToken === '-';
    }
}
