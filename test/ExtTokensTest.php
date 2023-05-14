<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @covers \ExtTokens
 */
class ExtTokensTest extends TestCase
{
    public function testExtTokensIterates()
    {
        if ( ! class_exists(ExtTokens::class)) {
            $this->markTestSkipped();
        }
        $extTokens = new ExtTokens(new ArrayIterator(['1.0', '1', '2', '3', '5', '[]']));
        $this->assertInstanceOf(Iterator::class, $extTokens);
        $this->assertSame(['1.01235', '[', ']'], iterator_to_array($extTokens));
    }
}
