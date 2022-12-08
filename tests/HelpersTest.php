<?php

namespace RonasIT\Support\Tests;

class HelpersTest extends HelpersTestCase
{
    public function getData(): array
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
            ]
        ];
    }

    /**
     * @dataProvider getData
     *
     * @param string $input
     * @param string $key
     * @param string $expected
     */
    public function testGetList(string $input, string $key, string $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_get_list($input, $key);

        $this->assertEqualsFixture($expected, $result);
    }

    public function getIsMultidimensionalData(): array
    {
        return [
            [
                'input' => [1, 2, 3],
                'expected' => false
            ],
            [
                'input' => [
                    'a' => 'b',
                    'c' => 'd',
                    'f' => [1, 2, 3]
                ],
                'expected' => false
            ],
            [
                'input' => [
                    [
                        'a' => 1,
                        'b' => 2
                    ]
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getIsMultidimensionalData
     *
     * @param array $input
     * @param bool $expected
     */
    public function testIsMultidimensional(array $input, bool $expected)
    {
        $result = is_multidimensional($input);

        $this->assertEquals($expected, $result);
    }
}
