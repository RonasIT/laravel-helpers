<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Traits\MockTrait;

class HelpersTest extends HelpersTestCase
{
    use MockTrait;

    public static function getGetListData(): array
    {
        return [
            [
                'input' => 'city',
                'key' => 'neighborhoods.*.zips.*.state',
                'expected' => 'states',
            ],
            [
                'input' => 'neighborhood',
                'key' => 'zips.*.code',
                'expected' => 'neighborhood.zips.codes',
            ],
            [
                'input' => 'city',
                'key' => 'neighborhoods.*.zips.*.code',
                'expected' => 'city.neighborhoods.zips.codes',
            ],
            [
                'input' => 'city',
                'key' => 'neighborhoods.*.zips',
                'expected' => 'city.neighborhoods.zips',
            ],
            [
                'input' => 'city',
                'key' => 'neighborhoods',
                'expected' => 'city.neighborhoods',
            ],
            [
                'input' => 'neighborhood',
                'key' => 'zips',
                'expected' => 'neighborhood.zips',
            ],
            [
                'input' => 'areas',
                'key' => 'zips.*.area.houses.*.number',
                'expected' => 'areas.houses',
            ],
        ];
    }

    #[DataProvider('getGetListData')]
    public function testGetList(string $input, string $key, string $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_get_list($input, $key);

        $this->assertEqualsFixture($expected, $result);
    }

    public static function getIsMultidimensionalData(): array
    {
        return [
            [
                'input' => 'areas.houses',
                'expected' => false,
            ],
            [
                'input' => 'areas',
                'expected' => false,
            ],
            [
                'input' => 'city.neighborhoods',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('getIsMultidimensionalData')]
    public function testIsMultidimensional(string $input, bool $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = is_multidimensional($input);

        $this->assertEquals($expected, $result);
    }

    public static function getEqualsData(): array
    {
        return [
            [
                'firstArray' => 'array_equals/settings',
                'secondArray' => 'array_equals/settings_diff',
                'expected' => false,
            ],
            [
                'firstArray' => 'array_equals/settings_rather_types',
                'secondArray' => 'array_equals/settings_rather_types_diff_order',
                'expected' => true,
            ],
            [
                'firstArray' => 'array_equals/settings',
                'secondArray' => 'array_equals/settings_diff_order',
                'expected' => true,
            ],
            [
                'firstArray' => 'areas.houses',
                'secondArray' => 'array_equals/non_associative',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('getEqualsData')]
    public function testEquals(string $firstArray, string $secondArray, bool $expected)
    {
        $firstArray = $this->getJsonFixture($firstArray);
        $secondArray = $this->getJsonFixture($secondArray);

        $result = array_equals($firstArray, $secondArray);

        $this->assertEquals($expected, $result);
    }

    public function testArrayRound()
    {
        $input = $this->getJsonFixture('array_round/values');

        $result = array_round($input);

        $this->assertEqualsFixture('array_round/rounded_values', $result);
    }

    public static function getArrayDuplicatesData(): array
    {
        return [
            [
                'input' => 'array_get_duplicates/numeric_array',
                'expected' => 'array_get_duplicates/numeric_array_duplicates',
            ],
            [
                'input' => 'array_get_duplicates/string_array',
                'expected' => 'array_get_duplicates/string_array_duplicates',
            ],
            [
                'input' => 'array_get_duplicates/complex_array',
                'expected' => 'array_get_duplicates/complex_array_duplicates',
            ],
        ];
    }

    #[DataProvider('getArrayDuplicatesData')]
    public function testArrayGetDuplicate(string $input, string $expected)
    {
        $input = $this->getJsonFixture($input);

        $result = array_get_duplicates($input);

        $this->assertEqualsFixture($expected, $result);
    }

    public static function getArrayUniqueObjectsData(): array
    {
        return [
            [
                'filter' => 'id',
                'expected' => 'array_unique_objects/unique_objects_filtered_by_string_key',
            ],
            [
                'filter' => ['name'],
                'expected' => 'array_unique_objects/unique_objects_filtered_by_array_key',
            ],
            [
                'filter' => fn ($objet) => $objet['id'],
                'expected' => 'array_unique_objects/unique_objects_filtered_by_callback_key',
            ],
        ];
    }

    #[DataProvider('getArrayUniqueObjectsData')]
    public function testArrayUniqueObjects(string|callable|array $filter, string $expected)
    {
        $input = $this->getJsonFixture('array_unique_objects/array_with_duplicates');

        $result = array_unique_objects($input, $filter);

        $this->assertEqualsFixture($expected, $result);
    }

    public function testArrayTrim()
    {
        $input = $this->getJsonFixture('array_trim/data');

        $result = array_trim($input);

        $this->assertEqualsFixture('array_trim/result', $result);
    }

    public static function getArrayRemoveByFieldData(): array
    {
        return [
            [
                'field' => 'id',
                'value' => 1,
                'expected' => 'array_remove_by_field/result_remove_by_id',
            ],
            [
                'field' => 'name',
                'value' => 'test2',
                'expected' => 'array_remove_by_field/result_remove_by_name',
            ],
        ];
    }

    #[DataProvider('getArrayRemoveByFieldData')]
    public function testArrayRemoveByField(string $field, string|int $value, string $expected)
    {
        $input = $this->getJsonFixture('array_remove_by_field/data');

        $result = array_remove_by_field($input, $field, $value);

        $this->assertEqualsFixture($expected, $result);
    }

    public function testArrayUndot()
    {
        $input = $this->getJsonFixture('array_undot/data');

        $result = array_undot($input);

        $this->assertEqualsFixture('array_undot/result', $result);
    }

    public function testArrayAssociate()
    {
        $input = $this->getJsonFixture('array_associate/data');

        $result = array_associate($input, function ($value, $key) {
            return [
                'key' => "prepared_{$key}",
                'value' => $value,
            ];
        });

        $this->assertEqualsFixture('array_associate/result', $result);
    }

    public function testArraySubtraction()
    {
        $input1 = $this->getJsonFixture('array_subtraction/data1');
        $input2 = $this->getJsonFixture('array_subtraction/data2');

        $result = array_subtraction($input1, $input2);

        $this->assertEqualsFixture('array_subtraction/result', $result);
    }

    public function testArrayRemoveElements()
    {
        $input1 = $this->getJsonFixture('array_remove_elements/data1');
        $input2 = $this->getJsonFixture('array_remove_elements/data2');

        $result = array_remove_elements($input1, $input2);

        $this->assertEqualsFixture('array_remove_elements/result', $result);
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
        $input = $this->getJsonFixture('fPutQuotedCsv/input');

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
