<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\Decoder;
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
    private $jsonPointerPath;

    /** @var string */
    private $jsonPointer;

    /** @var Decoder */
    private $jsonDecoder;

    /**
     * @param Traversable $lexer
     * @param string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param Decoder $jsonDecoder
     */
    public function __construct(Traversable $lexer, $jsonPointer = '', $jsonDecoder = null)
    {
        if (0 === preg_match('_^(/(([^/~])|(~[01]))*)*$_', $jsonPointer, $matches)) {
            throw new InvalidArgumentException(
                "Given value '$jsonPointer' of \$jsonPointer is not valid JSON Pointer"
            );
        }

        $this->lexer = $lexer;
        $this->jsonPointer = $jsonPointer;
        $this->jsonPointerPath = array_slice(array_map(function ($jsonPointerPart) {
            return str_replace(
                '~0',
                '~',
                str_replace('~1', '/', $jsonPointerPart)
            );
        }, explode('/', $jsonPointer)), 1);
        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder(true);
    }

    /**
     * @return \Generator
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

        $iteratorLevel = count($this->jsonPointerPath);
        $iteratorStruct = null;
        $currentPath = [];
        $pathFound = false;
        $currentLevel = -1;
        $stack = [$currentLevel => null];
        $jsonBuffer = '';
        $key = null;
        $previousToken = null;
        $objectKeyExpected = false;
        $inObject = true; // hack to make "!$inObject" in first iteration work. Better code structure?
        $expectedType = self::OBJECT_START | self::ARRAY_START;
        $subtreeEnded = false;
        $token = null;

        // local variables for faster name lookups
        $lexer = $this->lexer;
        $jsonPointerPath = $this->jsonPointerPath;

        foreach ($lexer as $token) {
            $tokenType = ${$token[0]};
            if (0 === ($tokenType & $expectedType)) {
                $this->error("Unexpected symbol", $token);
            }
            $isValue = ($tokenType | 23) === 23; // 23 = self::ANY_VALUE
            if (! $inObject && $isValue && $currentLevel < $iteratorLevel) {
                if ($jsonPointerPath[$currentLevel] === '-') {
                    $currentPath[$currentLevel] = '-';
                } else {
                    $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? (string)(1+(int)$currentPath[$currentLevel]) : "0";
                }
                unset($currentPath[$currentLevel+1]);
            }
            if ($currentPath === $jsonPointerPath
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
                            $keyResult = $this->jsonDecoder->decodeKey($token);
                            if (! $keyResult->isOk()) {
                                $this->error($keyResult->getErrorMessage(), $token);
                            }
                            // fixme: If there's an error in a key outside the iterator level and ErrorWrappingDecoder
                            // fixme: is used, DecodingError is saved in $currentPath instead of throwing an exception.
                            // fixme: The parser will go on, but silently ignore a possibly matching collection.
                            // fixme: Possible solutions: hard dependency on json_decode or add Decoder::decodeInternalKey()
                            $currentPath[$currentLevel] = $keyResult->getValue();
                            unset($currentPath[$currentLevel+1]);
                        }
                        continue 2; // valid json chunk is not completed yet
                    } else {
                        if ($inObject) {
                            $expectedType = 72; // 72 = self::AFTER_OBJECT_VALUE;
                        } else {
                            $expectedType = 96; // 96 = self::AFTER_ARRAY_VALUE;
                        }
                    }
                    break;
                case ',':
                    if ($inObject) {
                        $objectKeyExpected = true;
                        $expectedType = 2; // 2 = self::SCALAR_STRING
                    } else {
                        $expectedType = 23; // 23 = self::ANY_VALUE
                    }
                    continue 2; // valid json chunk is not completed yet
                case ':':
                    $expectedType = 23; // 23 = self::ANY_VALUE
                    continue 2; // valid json chunk is not completed yet
                case '{':
                    ++$currentLevel;
                    if ($currentLevel <= $iteratorLevel) {
                        $iteratorStruct = '{';
                    }
                    $stack[$currentLevel] = '{';
                    $inObject = true;
                    $expectedType = 10; // 10 = self::AFTER_OBJECT_START
                    $objectKeyExpected = true;
                    continue 2; // valid json chunk is not completed yet
                case '[':
                    ++$currentLevel;
                    if ($currentLevel <= $iteratorLevel) {
                        $iteratorStruct = '[';
                    }
                    $stack[$currentLevel] = '[';
                    $inObject = false;
                    $expectedType = 55; // 55 = self::AFTER_ARRAY_START;
                    continue 2; // valid json chunk is not completed yet
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
            if (! $pathFound && $currentPath === $jsonPointerPath) {
                $pathFound = true;
            }
            if ($pathFound && $currentPath !== $jsonPointerPath) {
                $subtreeEnded = true;
                break;
            }
            if ($currentLevel <= $iteratorLevel && $jsonBuffer !== '') {
                $valueResult = $this->jsonDecoder->decodeValue($jsonBuffer);
                if (! $valueResult->isOk()) {
                    $this->error($valueResult->getErrorMessage(), $token);
                }
                if ($iteratorStruct === '[') {
                    yield $valueResult->getValue();
                    $jsonBuffer = '';
                } else {
                    $keyResult = $this->jsonDecoder->decodeKey($key);
                    if (! $keyResult->isOk()) {
                        $this->error($keyResult->getErrorMessage(), $key);
                    }
                    yield $keyResult->getValue() => $valueResult->getValue();
                    $jsonBuffer = '';
                }
            }
        }

        if ($token === null) {
            $this->error('Cannot iterate empty JSON', $token);
        }

        if ($currentLevel > -1 && ! $subtreeEnded) {
            $this->error('JSON string ended unexpectedly', $token, UnexpectedEndSyntaxErrorException::class);
        }

        if (! $pathFound) {
            throw new PathNotFoundException("Path '{$this->jsonPointer}' was not found in json stream.");
        }
    }

    /**
     * @return array
     */
    public function getJsonPointerPath()
    {
        return $this->jsonPointerPath;
    }

    /**
     * @return string
     */
    public function getJsonPointer()
    {
        return $this->jsonPointer;
    }

    private function error($msg, $token, $exception = SyntaxError::class)
    {
        throw new $exception($msg." '".$token."'", $this->lexer->getPosition());
    }

    public function getPosition()
    {
        if ($this->lexer instanceof PositionAware) {
            return $this->lexer->getPosition();
        } else {
            throw new JsonMachineException('Provided lexer must implement PositionAware to call getPosition on it.');
        }
    }
}
