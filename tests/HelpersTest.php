<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Traits\MockTrait;

class HelpersTest extends HelpersTestCase
{
    use MockTrait;

    public function getGetListData(): array
    {
        return [
            [
                'input' => 'city.json',
                'key' => 'neighborhoods.*.zips.*.state',
                'expected' => 'states.json',
            ],
            [
                'input' => 'neighborhood.json',
                'key' => 'zips.*.code',
                'expected' => 'neighborhood.zips.codes.json',
            ],
            [
                'input' => 'city.json',
                'key' => 'neighborhoods.*.zips.*.code',
                'expected' => 'city.neighborhoods.zips.codes.json',
            ],
            [
                'input' => 'city.json',
                'key' => 'neighborhoods.*.zips',
                'expected' => 'city.neighborhoods.zips.json',
            ],
            [
                'input' => 'city.json',
                'key' => 'neighborhoods',
                'expected' => 'city.neighborhoods.json',
            ],
            [
                'input' => 'neighborhood.json',
                'key' => 'zips',
                'expected' => 'neighborhood.zips.json',
            ],
            [
                'input' => 'areas.json',
                'key' => 'zips.*.area.houses.*.number',
                'expected' => 'areas.houses.json',
            ],
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
                'expected' => false,
            ],
            [
                'input' => 'areas.json',
                'expected' => false,
            ],
            [
                'input' => 'city.neighborhoods.json',
                'expected' => true,
            ],
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
                'expected' => false,
            ],
            [
                'first_array' => 'array_equals/settings_rather_types.json',
                'second_array' => 'array_equals/settings_rather_types_diff_order.json',
                'expected' => true,
            ],
            [
                'first_array' => 'array_equals/settings.json',
                'second_array' => 'array_equals/settings_diff_order.json',
                'expected' => true,
            ],
            [
                'first_array' => 'areas.houses.json',
                'second_array' => 'array_equals/non_associative.json',
                'expected' => true,
            ],
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
            ],
        ];
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
                'filter' => function ($objet) {
                    return $objet['id'];
                },
                'expected' => 'array_unique_objects/unique_objects_filtered_by_callback_key.json',
            ],
        ];
    }

    /**
     * @dataProvider getArrayUniqueObjectsData
     *
     * @param string|callable|array $filter
     * @param string $expected
     */
    public function testArrayUniqueObjects(string|callable|array $filter, string $expected)
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

    public function getArrayRemoveByFieldData(): array
    {
        return [
            [
                'field' => 'id',
                'value' => 1,
                'expected' => 'array_remove_by_field/result_remove_by_id.json',
            ],
            [
                'field' => 'name',
                'value' => 'test2',
                'expected' => 'array_remove_by_field/result_remove_by_name.json',
            ],
        ];
    }

    /**
     * @dataProvider getArrayRemoveByFieldData
     *
     * @param string  $field
     * @param string|int $value
     * @param string $expected
     */
    public function testArrayRemoveByField(string $field, string|int $value, string $expected)
    {
        $input = $this->getJsonFixture('array_remove_by_field/data.json');

        $result = array_remove_by_field($input, $field, $value);

        $this->assertEqualsFixture($expected, $result);
    }

    public function testArrayUndot()
    {
        $input = $this->getJsonFixture('array_undot/data.json');

        $result = array_undot($input);

        $this->assertEqualsFixture('array_undot/result.json', $result);
    }

    public function testArrayAssociate()
    {
        $input = $this->getJsonFixture('array_associate/data.json');

        $result = array_associate($input, function ($value, $key) {
            return [
                'key' => "prepared_{$key}",
                'value' => $value,
            ];
        });

        $this->assertEqualsFixture('array_associate/result.json', $result);
    }

    public function testArraySubtraction()
    {
        $input1 = $this->getJsonFixture('array_subtraction/data1.json');
        $input2 = $this->getJsonFixture('array_subtraction/data2.json');

        $result = array_subtraction($input1, $input2);

        $this->assertEqualsFixture('array_subtraction/result.json', $result);
    }

    public function testArrayRemoveElements()
    {
        $input1 = $this->getJsonFixture('array_remove_elements/data1.json');
        $input2 = $this->getJsonFixture('array_remove_elements/data2.json');

        $result = array_remove_elements($input1, $input2);

        $this->assertEqualsFixture('array_remove_elements/result.json', $result);
    }

    public function testMkDirRecursively()
    {
        mkdir_recursively('dir1/dir2/dir3');

        $this->assertTrue(file_exists('dir1'));
        $this->assertTrue(file_exists('dir1/dir2'));
        $this->assertTrue(file_exists('dir1/dir2/dir3'));

        rmdir_recursively('dir1');
    }

    public function testClearFolder()
    {
        mkdir_recursively('dir1/dir2/dir3');
        file_put_contents('dir1/file1.txt', '');
        file_put_contents('dir1/dir2/file2.txt', '');
        file_put_contents('dir1/dir2/dir3/file3.txt', '');

        clear_folder('dir1/dir2/dir3');

        $this->assertFalse(file_exists('dir1/dir2/dir3/file3.txt'));
        $this->assertTrue(file_exists('dir1/dir2/dir3'));

        clear_folder('dir1');

        $this->assertFalse(file_exists('dir1/file1.txt'));
        $this->assertFalse(file_exists('dir1/dir2/file2.txt'));
        $this->assertTrue(file_exists('dir1/dir2'));
        $this->assertTrue(file_exists('dir1/dir2/dir3'));

        rmdir_recursively('dir1');
    }

    public function testFPutQuotedCsv()
    {
        $input = $this->getJsonFixture('fPutQuotedCsv/input.json');

        $fp = fopen('test.csv', 'w');

        foreach ($input as $item) {
            fPutQuotedCsv($fp, $item);
        }

        fclose($fp);

        $fixture = $this->getFixture('fPutQuotedCsv/result.csv');
        $file = file_get_contents('test.csv');

        $this->assertEquals($fixture, $file);

        unlink('test.csv');
    }

    public function testArrayDefault()
    {
        $array = [
            'first_name' => 'John',
            'company' => 'Acme',
        ];

        array_default($array, 'first_name', 'Sam');

        $this->assertEquals('John', $array['first_name']);

        array_default($array, 'last_name', 'Smith');

        $this->assertEquals('Smith', $array['last_name']);
    }
}
