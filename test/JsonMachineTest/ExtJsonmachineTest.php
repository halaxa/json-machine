<?php

namespace JsonMachineTest;

use PHPUnit\Framework\TestCase;

class ExtJsonmachineTest extends TestCase
{
    public function xtestExtensionLoaded()
    {
        $this->assertTrue(function_exists('jsonmachine_next_token'));
        jsonmachine_next_token('{}');
        jsonmachine_next_token('{}', true);
        jsonmachine_next_token('{}', false);
    }
}
