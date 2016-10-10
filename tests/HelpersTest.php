<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 09.10.16
 * Time: 20:29
 */

namespace RonasIT\Support\Tests;

class HelpersTest extends TestCase
{
    public function getData() {
        return [
            [
                'array' => 'neighborhood.json',
                'key' => 'zips.*.code',
                'expected' => 'neighborhood.zips.codes.json'
            ],
            [
                'array' => 'city.json',
                'key' => 'neighborhoods.*.zips.*.code',
                'expected' => 'city.neighborhoods.zips.codes.json'
            ],
            [
                'array' => 'city.json',
                'key' => 'neighborhoods.*.zips',
                'expected' => 'city.neighborhoods.zips.json'
            ],
            [
                'array' => 'city.json',
                'key' => 'neighborhoods',
                'expected' => 'city.neighborhoods.json'
            ],

            [
                'array' => 'neighborhood.json',
                'key' => 'zips',
                'expected' => 'neighborhood.zips.json'
            ]
        ];
    }

    /**
     * @dataProvider getData
     *
     * @param array $input
     * @param string $key
     * @param array $expected
    */
    public function testGetList($input, $key, $expected) {
        $input = $this->getJsonFixture($input);

        $result = array_get_list($input, $key);

        $this->assertEquals(
            $this->getJsonFixture($expected), $result
        );
    }
}