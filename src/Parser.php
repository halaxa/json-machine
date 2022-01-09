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
    private $jsonPointerPath;

    /** @var string */
    private $jsonPointer;

    /** @var ItemDecoder */
    private $jsonDecoder;



    /** @var int */
    private $iteratorLevel;

    private $iteratorStruct = null;

    private $currentPath = [];

    private $pathFound = false;

    private $currentLevel = -1;

    private $stack = [];

    private $jsonBuffer = '';

    private $key = null;

    private $objectKeyExpected = false;

    private $inObject = true; // hack to make "!$inObject" in first iteration work. Better code structure?

    private $expectedType = self::OBJECT_START | self::ARRAY_START;

    private $subtreeEnded = false;

    private $token = null;


    /**
     * @param Traversable $lexer
     * @param string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param ItemDecoder $jsonDecoder
     */
    public function __construct(Traversable $lexer, $jsonPointer = '', ItemDecoder $jsonDecoder = null)
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
        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder();

        $this->iteratorLevel = count($this->jsonPointerPath);
        $this->stack = [$this->currentLevel => null];
    }

    /**
     * @return \Generator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        // expand token types to local variables for the fastest lookup
        foreach ($this->tokenTypes() as $firstByte => $type) {
            ${$firstByte} = $type;
        }

        $iteratorLevel = $this->iteratorLevel;
        $iteratorStruct = $this->iteratorStruct;
        $currentPath = $this->currentPath;
        $pathFound = $this->pathFound;
        $currentLevel = $this->currentLevel;
        $stack = $this->stack;
        $jsonBuffer = $this->jsonBuffer;
        $key = $this->key;
        $objectKeyExpected = $this->objectKeyExpected;
        $inObject = $this->inObject;
        $expectedType = $this->expectedType;
        $subtreeEnded = $this->subtreeEnded;
        $token = $this->token;

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
                            $keyResult = $this->jsonDecoder->decodeInternalKey($token);
                            if (! $keyResult->isOk()) {
                                $this->error($keyResult->getErrorMessage(), $token);
                            }
                            $currentPath[$currentLevel] = $keyResult->getValue();
                            unset($keyResult);
                            unset($currentPath[$currentLevel+1]);
                        }
                        continue 2; // a valid json chunk is not completed yet
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
            if (! $pathFound && $currentPath === $jsonPointerPath) {
                $pathFound = true;
            }
            if ($pathFound && $currentPath !== $jsonPointerPath) {
                $subtreeEnded = true;
                break;
            }
        }

        $this->iteratorLevel = $iteratorLevel;
        $this->iteratorStruct = $iteratorStruct;
        $this->currentPath = $currentPath;
        $this->pathFound = $pathFound;
        $this->currentLevel = $currentLevel;
        $this->stack = $stack;
        $this->jsonBuffer = $jsonBuffer;
        $this->key = $key;
        $this->objectKeyExpected = $objectKeyExpected;
        $this->inObject = $inObject;
        $this->expectedType = $expectedType;
        $this->subtreeEnded = $subtreeEnded;
        $this->token = $token;

        $this->end();
    }

    public function end()
    {
        if ($this->token === null) {
            $this->error('Cannot iterate empty JSON', $this->token);
        }

        if ($this->currentLevel > -1 && ! $this->subtreeEnded) {
            $this->error('JSON string ended unexpectedly', $this->token, UnexpectedEndSyntaxErrorException::class);
        }

        if (! $this->pathFound) {
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
}
