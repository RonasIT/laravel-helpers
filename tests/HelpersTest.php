<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\FixturesTrait;

class HelpersTest extends HelpersTestCase
{
    use FixturesTrait;

    public function getData()
    {
        return [
            [
                'array' => 'city.json',
                'key' => 'neighborhoods.*.zips.*.state',
                'expected' => 'states.json'
            ],
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
            ],
            [
                'array' => 'areas.json',
                'key' => 'zips.*.area.houses.*.number',
                'expected' => 'areas.houses.json'
            ],
        ];
    }

    /**
     * @dataProvider getData
     *
     * @param array $input
     * @param string $key
     * @param array $expected
     */
    public function testGetList($input, $key, $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_get_list($input, $key);

        $this->assertEquals(
            $this->getJsonFixture($expected), $result
        );
    }
}