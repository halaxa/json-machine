<?php

namespace JsonMachineTest;

use PHPUnit\Framework\TestCase;

class ExtJsonmachineTest extends TestCase
{
    public function testExtensionLoaded()
    {
        $this->assertTrue(function_exists('jsonmachine_next_token'));
        while($token = jsonmachine_next_token('chunk', $tokenBuffer, $escaping, $inString, $lastIndex)) {
            var_dump($token);
            var_dump($tokenBuffer, $escaping, $inString, $lastIndex);
        }
        if ($tokenBuffer) {
            var_dump($tokenBuffer);
        }
    }
}
