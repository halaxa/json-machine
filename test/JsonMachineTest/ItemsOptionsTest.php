<?php

namespace JsonMachineTest;

use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\ItemsOptions;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use PHPUnit\Framework\TestCase;

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
     * @dataProvider dataOptionNames
     */
    public function testThrowsOnInvalidOptionType($option)
    {
        $this->expectException(InvalidArgumentException::class);

        new ItemsOptions([$option => new InvalidValue()]);
    }

    public function dataOptionNames()
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
}

class InvalidValue
{
}
