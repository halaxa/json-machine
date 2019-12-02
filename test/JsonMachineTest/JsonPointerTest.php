<?php

namespace JsonMachineTest;

use JsonMachine\JsonMachine;

class JsonPointerTest extends \PHPUnit_Framework_TestCase
{
    public function testObjectsBeforeArrayAreParsedProperly()
    {
        $jsonPointer = '/datafeed/programs';
        echo getcwd();
        $programs = JsonMachine::fromFile(getcwd().'/test/JsonMachineTest/JsonPointerTest.json', $jsonPointer);
        $this->assertCount(1, $programs);
        foreach ($programs as $program) {
            $this->assertCount(2, $program);
        }
    }

    public function testParserDoesntBreakOnArray()
    {
        $jsonPointer = '/datafeed/programs/0';
        echo getcwd();
        $firstProgram = JsonMachine::fromFile(getcwd().'/test/JsonMachineTest/JsonPointerTest.json', $jsonPointer);
        $this->assertCount(2, $firstProgram);
    }
}
