<?php

declare(strict_types=1);

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\ItemsOptions;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMachine\ItemsOptions
 */
class ItemsOptionsTest extends TestCase
{
    public function testReturnsAllOptionsWithDefaultValues()
    {
        $options = new ItemsOptions();
        $optionsArray = $options->toArray();

        $this->assertEquals($this->defaultOptions(), $optionsArray);
    }

    public function testHasArrayAccess()
    {
        $options = new ItemsOptions();

        $this->assertTrue(isset($options['debug']));
        $this->assertFalse($options['debug']);
    }

    /**
     * @dataProvider defaultOptionNames
     */
    public function testThrowsOnInvalidOptionType($optionName)
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemsOptions([$optionName => new InvalidValue()]);
    }

    public function defaultOptionNames()
    {
        foreach ($this->defaultOptions() as $name => $ignore) {
            yield [$name];
        }
    }

    private function defaultOptions()
    {
        return [
            'pointer' => '',
            'decoder' => new ExtJsonDecoder(),
            'debug' => false,
        ];
    }

    public function testThrowsOnUnknownOption()
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemsOptions(['invalid_option_name' => 'value']);
    }

    public function testSuggestsCorrectOption()
    {
        $this->expectExceptionMessage("'debug'");
        new ItemsOptions(['degub' => true]);
    }
}

class InvalidValue
{
}
