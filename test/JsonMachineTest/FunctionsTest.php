<?php

namespace JsonMachineTest;

use Symfony\Component\HttpClient\HttpClient;
use function JsonMachine\httpClientChunks;
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

    public function testHttpClientChunks()
    {
        if (PHP_VERSION_ID < 70205) {
            $this->markTestSkipped("Symfony HttpClient supports PHP >= 7.2.5");
        }

        $url = 'https://httpbin.org/anything?key=value';
        $client = HttpClient::create();
        $response = $client->request('GET', $url);

        $this->assertSame(200, $response->getStatusCode());

        $result = json_decode(implode(iterator_to_array(httpClientChunks($client->stream($response)))), true);
        $this->assertSame(['key'=>'value'], $result['args']);
    }
}
