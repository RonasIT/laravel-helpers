<?php

namespace RonasIT\Support\Tests;

class HelpersTest extends HelpersTestCase
{
    public function getGetListData(): array
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
     * @dataProvider getGetListData
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
                'input' => 'areas.houses.json',
                'expected' => false
            ],
            [
                'input' => 'areas.json',
                'expected' => false
            ],
            [
                'input' => 'city.neighborhoods.json',
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getIsMultidimensionalData
     *
     * @param string $input
     * @param bool $expected
     */
    public function testIsMultidimensional(string $input, bool $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = is_multidimensional($input);

        $this->assertEquals($expected, $result);
    }

    public function getEqualsData(): array
    {
        return [
            [
                'first_array' => 'array_equals/settings.json',
                'second_array' => 'array_equals/settings_diff.json',
                'expected' => false
            ],
            [
                'first_array' => 'array_equals/settings_rather_types.json',
                'second_array' => 'array_equals/settings_rather_types_diff_order.json',
                'expected' => true
            ],
            [
                'first_array' => 'array_equals/settings.json',
                'second_array' => 'array_equals/settings_diff_order.json',
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getEqualsData
     *
     * @param string $firstArray
     * @param string $secondArray
     * @param bool $expected
     */
    public function testEquals(string $firstArray, string $secondArray, bool $expected)
    {
        $firstArray = $this->getJsonFixture($firstArray);
        $secondArray = $this->getJsonFixture($secondArray);

        $result = array_equals($firstArray, $secondArray);

        $this->assertEquals($expected, $result);
    }
}
