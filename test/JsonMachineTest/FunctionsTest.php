<?php

namespace JsonMachineTest;

use function JsonMachine\objects;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataObjectsOnEmptyInput
     * @param $expected
     * @param $data
     */
    public function testObjectsOnEmptyInput($expected, $data)
    {
        error_reporting(~E_USER_DEPRECATED);
        $this->assertEquals($expected, iterator_to_array(objects($data)));
    }

    public function dataObjectsOnEmptyInput()
    {
        return [
            [[], []],
            [[new \stdClass()], [[]]],
            [[(object)["one" => "two"]], [["one" => "two"]]],
        ];
    }
}
