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

        foreach (["[{\"one\": 1}, {\"two\": false}, {\"thr\\", "\"ee\": \"string\"}]"] as $chunk) {

            while($token = jsonmachine_next_token($chunk, $tokenBuffer, $escaping, $inString, $lastIndex)) {
//                var_dump($token, $tokenBuffer, $escaping, $inString, $lastIndex);
                var_dump($token);
                flush();
                ob_flush();
            }

//            var_dump($token, $tokenBuffer, $escaping, $inString, $lastIndex);
            var_dump($token);
            flush();
            ob_flush();
        }
    }
}
