<?php

namespace JsonMachineTest;

use PHPUnit\Framework\TestCase;

class ExtJsonmachineTest extends TestCase
{
    public function testExtensionLoaded()
    {
        $this->assertTrue(function_exists('jsonmachine_next_token'));

        $lastIndex = 0;
        $inString = false;
        $escaping = false;
        $tokenBuffer = "";

        while($token = jsonmachine_next_token('chunk', $tokenBuffer, $escaping, $inString, $lastIndex)) {
            var_dump($token, $tokenBuffer, $escaping, $inString, $lastIndex);
            flush();
            ob_flush();
        }
        var_dump($token, $tokenBuffer, $escaping, $inString, $lastIndex);
        flush();
        ob_flush();
    }
}
