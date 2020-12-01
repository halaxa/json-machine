<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\JsonMachineException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Exception\UnexpectedEndSyntaxErrorException;
use JsonMachine\JsonDecoder\Decoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

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

    const AFTER_ARRAY_START = self::ANY_VALUE | self::ARRAY_END;
    const AFTER_OBJECT_START = self::SCALAR_STRING | self::OBJECT_END;
    const AFTER_ARRAY_VALUE = self::COMMA | self::ARRAY_END;
    const AFTER_OBJECT_VALUE = self::COMMA | self::OBJECT_END;
    const ANY_VALUE = self::OBJECT_START | self::ARRAY_START | self::SCALAR_CONST | self::SCALAR_STRING;

    private $type = [
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

    /** @var Lexer */
    private $lexer;

    /** @var string */
    private $token;

    /** @var string */
    private $jsonPointerPath;

    /** @var string */
    private $jsonPointer;

    /** @var Decoder */
    private $jsonDecoder;

    /**
     * @param \Traversable $lexer
     * @param string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     * @param Decoder $jsonDecoder
     */
    public function __construct(\Traversable $lexer, $jsonPointer = '', $jsonDecoder = null)
    {
        if (0 === preg_match('_^(/(([^/~])|(~[01]))*)*$_', $jsonPointer, $matches)) {
            throw new InvalidArgumentException(
                "Given value '$jsonPointer' of \$jsonPointer is not valid JSON Pointer"
            );
        }

        $this->lexer = $lexer;
        $this->jsonPointer = $jsonPointer;
        $this->jsonPointerPath = array_slice(array_map(function ($jsonPointerPart){
            return str_replace(
                '~0', '~', str_replace('~1', '/', $jsonPointerPart)
            );
        }, explode('/', $jsonPointer)), 1);
        $this->jsonDecoder = $jsonDecoder ?: new ExtJsonDecoder(true);
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        // todo Allow to call getIterator only once per instance
        $iteratorLevel = count($this->jsonPointerPath);
        $iteratorStruct = null;
        $currentPath = [];
        $pathFound = false;
        $currentLevel = -1;
        $stack = [$currentLevel => null];
        $jsonBuffer = '';
        $key = null;
        $previousToken = null;
        $inArray = false; // todo remove one of inArray, inObject
        $inObject = false;
        $expectedType = self::OBJECT_START | self::ARRAY_START;

        foreach ($this->lexer as $this->token) {
            $firstChar = $this->token[0];
            if ( ! isset($this->type[$firstChar]) || ! ($this->type[$firstChar] & $expectedType)) {
                $this->error("Unexpected symbol");
            }
            if ($currentPath === $this->jsonPointerPath && ($currentLevel > $iteratorLevel || ($currentLevel === $iteratorLevel && $expectedType & self::ANY_VALUE))) {
                $jsonBuffer .= $this->token;
            }
            if ($currentLevel < $iteratorLevel && $inArray && $expectedType & self::ANY_VALUE) {
                $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? (string)(1+(int)$currentPath[$currentLevel]) : "0";
                unset($currentPath[$currentLevel+1]);
            }
            switch ($firstChar) {
                case '"':
                    if ($inObject && ($previousToken === ',' || $previousToken === '{')) {
                        $expectedType = self::COLON;
                        $previousToken = null;
                        if ($currentLevel === $iteratorLevel) {
                            $key = $this->token;
                            $jsonBuffer = '';
                        } elseif ($currentLevel < $iteratorLevel) {
                            // inlined
                            $keyResult = $this->jsonDecoder->decodeKey($this->token);
                            if ( ! $keyResult->isOk()) {
                                $this->error($keyResult->getErrorMessage());
                            }
                            // endinlined
                            $currentPath[$currentLevel] = $keyResult->getValue();
                            unset($currentPath[$currentLevel+1]);
                        }
                        break;
                    } else {
                        goto expectedTypeAfterValue;
                    }
                case ',':
                    if ($inObject) {
                        $expectedType = self::SCALAR_STRING;
                    } else {
                        $expectedType = self::ANY_VALUE;
                    }
                    $previousToken = ',';
                    break;
                case ':':
                    $expectedType = self::ANY_VALUE;
                    break;
                case '{':
                    ++$currentLevel;
                    if ($currentLevel === $iteratorLevel) {
                        $iteratorStruct = '{';
                    }
                    $stack[$currentLevel] = '{';
                    $inArray = !$inObject = true;
                    $expectedType = self::AFTER_OBJECT_START;
                    $previousToken = '{';
                    break;
                case '[':
                    ++$currentLevel;
                    if ($currentLevel === $iteratorLevel) {
                        $iteratorStruct = '[';
                    }
                    $stack[$currentLevel] = '[';
                    $inArray = !$inObject = false;
                    $expectedType = self::AFTER_ARRAY_START;
                    break;
                case '}':
                case ']':
                    --$currentLevel;
                    $inArray = !$inObject = $stack[$currentLevel] === '{';
                default:
                    expectedTypeAfterValue:
                    if ($inArray) {
                        $expectedType = self::AFTER_ARRAY_VALUE;
                    } else {
                        $expectedType = self::AFTER_OBJECT_VALUE;
                    }
            }
            if ( ! $pathFound && $currentPath == $this->jsonPointerPath) {
                $pathFound = true;
            }
            if ($currentLevel === $iteratorLevel && $jsonBuffer !== '') {
                if ($currentPath == $this->jsonPointerPath) {
                    $valueResult = $this->jsonDecoder->decodeValue($jsonBuffer);
                    // inlined
                    if ( ! $valueResult->isOk()) {
                        $this->error($valueResult->getErrorMessage());
                    }
                    // endinlined
                    if ($iteratorStruct === '[') {
                        yield $valueResult->getValue();
                    } else {
                        // inlined
                        $keyResult = $this->jsonDecoder->decodeKey($key);
                        if ( ! $keyResult->isOk()) {
                            $this->error($keyResult->getErrorMessage());
                        }
                        // endinlined
                        yield $keyResult->getValue() => $valueResult->getValue();
                    }
                }
                $jsonBuffer = '';
            }
        }

        if ($this->token === null) {
            $this->error('Cannot iterate empty JSON');
        }

        if ( ! $pathFound) {
            throw new PathNotFoundException("Path '{$this->jsonPointer}' was not found in json stream.");
        }

        if ($currentLevel > -1){
            $this->error('JSON string ended unexpectedly', UnexpectedEndSyntaxErrorException::class);
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

    private function error($msg, $exception = SyntaxError::class)
    {
        throw new $exception($msg." '".$this->token."'", $this->lexer->getPosition());
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
