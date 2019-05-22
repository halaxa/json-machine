<?php

namespace JsonMachineTest;

use JsonMachine\Token;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->line = 3;
        $this->column = 5;
        $this->type = Token::SCALAR_STRING;
        $this->value = '"a string literal"';
    }

    public function testThrowsErrorIfLineIsNotANumber()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type "string" for line parameter. Please provide an integer.');

        $token = new Token('3', $this->column, $this->type, $this->value);
    }

    public function testLineIsSet()
    {
        $token = new Token($this->line, $this->column, $this->type, $this->value);

        $this->assertSame($this->line, $token->getLine());
    }

    public function testThrowsErrorIfColumnIsNotANumber()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type "string" for column parameter. Please provide an integer.');

        $token = new Token($this->line, '5', $this->type, $this->value);
    }

    public function testColumnIsSet()
    {
        $token = new Token($this->line, $this->column, $this->type, $this->value);

        $this->assertSame($this->column, $token->getColumn());
    }

    public function testThrowsErrorIfTypeIsNotANumber()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type "string" for type parameter. Please use one of the class constants.');

        $token = new Token($this->line, $this->column, '2', $this->value);
    }

    public function testTypeIsSet()
    {
        $token = new Token($this->line, $this->column, $this->type, $this->value);

        $this->assertSame($this->type, $token->getType());
    }

    public function testThrowsErrorIfValueIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type "NULL" for value parameter. Please provide a string.');

        $token = new Token($this->line, $this->column, $this->type, null);
    }

    public function testValueIsSet()
    {
        $token = new Token($this->line, $this->column, $this->type, $this->value);

        $this->assertSame($this->value, $token->getValue());
    }
}
