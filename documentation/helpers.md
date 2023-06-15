[<< Readme][1]
[Traits >>][2]

## Functions

### array_get_list($array, $path): array

This function designed to get list of all values witch placed in `$path` in `$array`.

```php
$data = [
    'id' => 1,
    'some_value' => 'qweqwe',
    'nested_values' => [
        [
            'id' => 4,
            'another_some_value' => 'value',
            'next_level_of_nesting' => [
                [
                    'id' => 6,
                    'value' => 'h'
                ],
                [
                    'id' => 7,
                    'value' => 'e'
                ]
            ]
        ],
        [
            'id' => 5,
            'another_some_value' => 'value',
            'next_level_of_nesting' => [
                [
                    'id' => 8,
                    'value' => 'l'
                ],
                [
                    'id' => 9,
                    'value' => 'l'
                ]
            ]
        ],
        [
            'id' => 6,
            'another_some_value' => 'value',
            'next_level_of_nesting' => [
                [
                    'id' => 10,
                    'value' => 'o'
                ]
            ]
        ]
    ]
];

$result = array_get_list($data, 'nested_values.*.next_level_of_nesting.*.value'); //['h', 'e', 'l', 'l', 'o'];
````

### is_associative($array): bool

Verifies whether `$array` is associative array or a list

```php
$associative = [
    'key' => 'value'
];
 
$list = ['some', 'values'];

is_associative($associative); //true
is_associative($list); //false
````

### array_subtraction($array1, $array2): array

Return subtraction of `$array2` from `$array1`

```php
$array1 = [1, 2, 3];
$array2 = [1, 2];

$result = array_subtraction($array1, $array2); //[3]
````

### array_equals($array1, $array2): bool

Verifies whether two arrays are equals

```php
$array1 = [1, 2, 3];
$array2 = [1, 2];
$array3 = [3, 2, 1];

array_equals($array1, $array3); //true
array_equals($array1, $array2); //false
````

### array_round($array): array

Round all values in list of floats.

```php
$array = [1.4, 2.9, 1.534];

array_round($array); //[1, 3, 2]
````

### mkdir_recursively($path)

Create directory recursively. The native mkdir() function recursively create directory incorrectly.
Here is solution of this problem.

### rmdir_recursively($path)

Remove directory recursively with all nested files and directories.

### getGUID()

Generate GUID

### array_concat($array, $callback)

Concat results of callback call. The first `$array` argument should be an array of strings.

```php
$array = ['some', 'random', 'values'];

$result = array_concat($array, function ($value, $key) {
    return "{$key}. {$value}\n";
});

/**
0. some
1. random
2. values

 */ 
````

### clear_folder($path)

Remove all files and folders from `$path`.

### array_associate($array, $callback) (deprecated)

Builds an associative array by gotten keys and values.

```php
$array = [
    [
        'id' => 1,
        'value' => 'first'
    ],
    [
        'id' => 2,
        'value' => 'second'
    ],
    [
        'id' => 3,
        'value' => 'third'
    ]
];

$result = array_associate($array, function($value) {
    return [
        'key' => $value['id'],
        'value' => $value['value']
    ];
});

//[1 => "first", 2 => "second", 3 => "third"]
````

### array_get_duplicates($array)

Return duplicated values of input `$array`.

```php
$array = [1, 2, 2, 3];

array_get_duplicates($array);

//[2 => 2]
````

### array_unique_object($objectsList, $key = 'id')

Return unique objects from array by field

```php
>> $array = [
     [
       "id" => 1,
       "value" => "first",
     ],
     [
       "id" => 2,
       "value" => "second",
     ],
     [
       "id" => 2,
       "value" => "second",
     ],
     [
       "id" => 3,
       "value" => "third",
     ],
   ]
>>> $result = array_unique_object($array)
=> [
     0 => [
       "id" => 1,
       "value" => "first",
     ],
     1 => [
       "id" => 2,
       "value" => "second",
     ],
     3 => [
       "id" => 3,
       "value" => "third",
     ],
   ]
````

### array_undot($array)

inverse transformation from array_dot

```php
>>> $array = [
    'some.nested.value' => 1,
    'some.array.0.value' => 2,
    'some.array.1.value' => 3
];
>>> array_undot($array)
=> [
     "some" => [
       "nested" => [
         "value" => 1,
       ],
       "array" => [
         [
           "value" => 2,
         ],
         [
           "value" => 3,
         ],
       ],
     ],
   ]
````

[<< Readme][1]
[Traits >>][2]

[1]:../readme.md
[2]:traits.md
