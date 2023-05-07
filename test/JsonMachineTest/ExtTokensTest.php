<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\ExtTokens;

/**
 * @covers \JsonMachine\ExtTokens
 */
class ExtTokensTest extends TokensTest
{
    public function availableTokenizers()
    {
        if ( ! extension_loaded('jsonmachine')) {
            $this->markTestSkipped('jsonmachine extension not loaded');
        }

        return [
            'ext' => [ExtTokens::class],
        ];
    }
}
