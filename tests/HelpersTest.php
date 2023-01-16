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
            ],
            [
                'first_array' => 'areas.houses.json',
                'second_array' => 'array_equals/non_associative.json',
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

    public function testArrayRound()
    {
        $input = $this->getJsonFixture('array_round/values.json');

        $result = array_round($input);

        $this->assertEqualsFixture('array_round/rounded_values.json', $result);
    }

    public function getArrayDuplicatesData(): array
    {
        return [
            [
                'input' => 'array_get_duplicates/numeric_array.json',
                'expected' => 'array_get_duplicates/numeric_array_duplicates.json',
            ],
            [
                'input' => 'array_get_duplicates/string_array.json',
                'expected' => 'array_get_duplicates/string_array_duplicates.json',
            ],
            [
                'input' => 'array_get_duplicates/complex_array.json',
                'expected' => 'array_get_duplicates/complex_array_duplicates.json',
            ]
        ];
    }

    /**
     * @dataProvider getArrayDuplicatesData
     *
     * @param string $input
     * @param string $expected
     */
    public function testArrayDuplicate(string $input, string $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_duplicate($input);

        $this->assertEqualsFixture($expected, $result);
    }

    /**
     * @dataProvider getArrayDuplicatesData
     *
     * @param string $input
     * @param string $expected
     */
    public function testArrayGetDuplicate(string $input, string $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_get_duplicates($input);

        $this->assertEqualsFixture($expected, $result);
    }

    public function getArrayUniqueObjectsData(): array
    {
        return [
            [
                'filter' => 'id',
                'expected' => 'array_unique_objects/unique_objects_filtered_by_string_key.json',
            ],
            [
                'filter' => ['name'],
                'expected' => 'array_unique_objects/unique_objects_filtered_by_array_key.json',
            ],
            [
                'filter' => function($objet) {
                    return $objet['id'];
                },
                'expected' => 'array_unique_objects/unique_objects_filtered_by_callback_key.json',
            ]
        ];
    }

    /**
     * @dataProvider getArrayUniqueObjectsData
     *
     * @param string|callable|array  $filter
     * @param string $expected
     */
    public function testArrayUniqueObjects($filter, string $expected)
    {
        $input = $this->getJsonFixture('array_unique_objects/array_with_duplicates.json');

        $result = array_unique_objects($input, $filter);

        $this->assertEqualsFixture($expected, $result);
    }

    public function testArrayTrim()
    {
        $input = $this->getJsonFixture('array_trim/data.json');

        $result = array_trim($input);

        $this->assertEqualsFixture('array_trim/result.json', $result);
    }
}
