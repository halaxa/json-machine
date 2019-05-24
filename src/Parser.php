<?php

namespace JsonMachine;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Exception\PathNotFoundException;
use JsonMachine\Exception\SyntaxError;
use JsonMachine\Token;

class Parser implements \IteratorAggregate
{
    const AFTER_ARRAY_START = self::ANY_VALUE | Token::ARRAY_END;
    const AFTER_OBJECT_START = Token::SCALAR_STRING | Token::OBJECT_END;
    const AFTER_ARRAY_VALUE = Token::COMMA | Token::ARRAY_END;
    const AFTER_OBJECT_VALUE = Token::COMMA | Token::OBJECT_END;
    const ANY_VALUE = Token::OBJECT_START | Token::ARRAY_START | Token::SCALAR_CONST | Token::SCALAR_STRING;

    private $type = [
        'n' => Token::SCALAR_CONST,
        't' => Token::SCALAR_CONST,
        'f' => Token::SCALAR_CONST,
        '-' => Token::SCALAR_CONST,
        '0' => Token::SCALAR_CONST,
        '1' => Token::SCALAR_CONST,
        '2' => Token::SCALAR_CONST,
        '3' => Token::SCALAR_CONST,
        '4' => Token::SCALAR_CONST,
        '5' => Token::SCALAR_CONST,
        '6' => Token::SCALAR_CONST,
        '7' => Token::SCALAR_CONST,
        '8' => Token::SCALAR_CONST,
        '9' => Token::SCALAR_CONST,
        '"' => Token::SCALAR_STRING,
        '{' => Token::OBJECT_START,
        '}' => Token::OBJECT_END,
        '[' => Token::ARRAY_START,
        ']' => Token::ARRAY_END,
        ',' => Token::COMMA,
        ':' => Token::COLON,
    ];

    /** @var Lexer */
    private $lexer;

    /** @var string */
    private $token;

    /** @var string */
    private $jsonPointerPath;

    /** @var string */
    private $jsonPointer;

    /**
     * @param \Traversable $lexer
     * @param string $jsonPointer Follows json pointer RFC https://tools.ietf.org/html/rfc6901
     */
    public function __construct(\Traversable $lexer, $jsonPointer = '')
    {
        if (0 === preg_match('_^(/(([^/~])|(~[01]))*)*$_', $jsonPointer, $matches)) {
            throw new InvalidArgumentException(
                "Given value '$jsonPointer' of \$jsonPointer is not valid JSON Pointer"
            );
        }

        $this->lexer = $lexer;
        $this->jsonPointer = $jsonPointer;
        $this->jsonPointerPath = array_slice(array_map(function ($jsonPointerPart){
            $jsonPointerPart = str_replace(
                '~0', '~', str_replace('~1', '/', $jsonPointerPart)
            );
            return is_numeric($jsonPointerPart) ? (int) $jsonPointerPart : $jsonPointerPart;
        }, explode('/', $jsonPointer)), 1);
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
        $expectedType = Token::OBJECT_START | Token::ARRAY_START;

        foreach ($this->lexer as $this->token) {
            $firstChar = $this->token->getValue()[0];
            if ( ! isset($this->type[$firstChar]) || ! ($this->type[$firstChar] & $expectedType)) {
                $this->error("Unexpected symbol");
            }
            if ($currentPath == $this->jsonPointerPath && ($currentLevel > $iteratorLevel || ($currentLevel === $iteratorLevel && $expectedType & self::ANY_VALUE))) {
                $jsonBuffer .= $this->token->getValue();
            }
            if ($currentLevel < $iteratorLevel && $inArray && $expectedType & self::ANY_VALUE) {
                $currentPath[$currentLevel] = isset($currentPath[$currentLevel]) ? (1+$currentPath[$currentLevel]) : 0;
            }
            switch ($firstChar) {
                case '"':
                    if ($inObject && ($previousToken === ',' || $previousToken === '{')) {
                        $expectedType = Token::COLON;
                        $previousToken = null;
                        if ($currentLevel === $iteratorLevel) {
                            $key = $this->token->getValue();
                            $jsonBuffer = '';
                        } elseif ($currentLevel < $iteratorLevel) {
                            $currentPath[$currentLevel] = json_decode($this->token->getValue());
                        }
                        break;
                    } else {
                        goto expectedTypeAfterValue;
                    }
                case ',':
                    if ($inObject) {
                        $expectedType = Token::SCALAR_STRING;
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
                    $value = json_decode($jsonBuffer, true);
                    if ($value === null && $jsonBuffer !== 'null') {
                        $this->error(json_last_error_msg());
                    }
                    if ($iteratorStruct === '[') {
                        yield $value;
                    } else {
                        yield json_decode($key) => $value;
                    }
                }
                $jsonBuffer = '';
            }
        }

        if ($this->token === null) {
            throw new SyntaxError("Cannot iterate empty JSON ''", $this->lexer->getPosition());
        }

        if ( ! $pathFound) {
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

    private function error($msg)
    {
        throw new SyntaxError($msg." '".$this->token->getValue()."'", $this->lexer->getPosition());
    }
}
