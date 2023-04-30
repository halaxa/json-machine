<?php

namespace JsonMachineTest;

use PHPUnit\Framework\TestCase;

class ExtJsonmachineTest extends TestCase
{
    public function testExtensionLoaded()
    {
        $this->assertTrue(function_exists('jsonmachine_next_token'));
        var_dump(jsonmachine_next_token(fopen('data://text/plain,XXX', 'r')));
//        jsonmachine_next_token('{}', true);
//        jsonmachine_next_token('{}', false);
    }
}
