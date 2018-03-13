[<< Back](../readme.md)

## Functions

### array_get_list($array, $path)
This function designed to get list of all values witch placed in $path in $array.
**Example**
```php
>>> $data = [
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
    
>>> $result = array_get_list($data, 'nested_values.*.next_level_of_nesting.*.value');
=> ['h', 'e', 'l', 'l', 'o'];
````

### is_associative($array)
Verifies whether an associative array or a list

```php
>>> $associative = [
        'key' => 'value'
    ]; 
>>> $list = ['some', 'values'];

>>> is_associative($associative);
=> true

>>> is_associative($list);
=> false

````

### array_subtraction($array1, $array2)
Return subtraction of two arrays

**Example**
```php
>>> $array1 = [1, 2, 3];
>>> $array2 = [1, 2];
>>> $result = array_subtraction($array1, $array2);
=> [3]
````

### array_equals($array1, $array2)
Check equivalency of two arrays

### array_round($array)
Round all values in list of floats. It designed just for density.

```php
>>> $result = array([1.4, 2.9, 1.534]);
=> [1, 3, 2]
````

### elseChain(...$callbacks)
This feature is designed to get the first non-empty function result. It does not make sense in php7, 
but can be useful when developing applications on php5.  
**Example**
```php
    $value = elseChain(
        function() use ($request, $code) {
            return empty($request) ? Response::$statusTexts[$code] : null;
        },
        function() use ($request, $code) {
            return $this->annotationReader->getClassAnnotations($request)->get("_{$code}");
        },
        function() use ($code) {
            return config("auto-doc.defaults.code-descriptions.{$code}");
        },
        function() use ($code) {
            return Response::$statusTexts[$code];
        }
    );
````


### mkdir_recursively($path)
Create directory recursively. The native mkdir() function recursively create directory incorrectly.
Here is solution of this problem.

### rmdir_recursively($path)
Remove directory recursively with all nested files and directories.

### getGUID()
Generate GUID

### array_concat($array, $callback)
Concat results of callback call. Array should be array of strings. Arguments of callback are `$value`, `$key`

```php
    >>> $array = ['some', 'random', 'values'];
    >>> $result = array_concat($array, function ($value, $key) {
            return "{$key}. {$value}\n";
        });
    => """
       0. some\n
       1. random\n
       2. values\n
       """
````

### clear_folder($path)
Remove all files and folders from `$path`

### array_associate($array, $callback)
Builds an associative array by gotten keys and values. Arguments of callback id `$value`, `$key`

```php
>>> $array = [
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

>>> $result = array_associate($array, function($value) {
    return [
        'key' => $value['id'],
        'value' => $value['value']
    ];
});
=> [
     1 => "first",
     2 => "second",
     3 => "third",
   ]
````

### array_duplicate($array)
Return duplicated values of array

```php
>>> $array = [1, 2, 2, 3];
>>> array_duplicate($array);
=> [
     2 => 2,
   ]
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

[<< Back](../readme.md)
