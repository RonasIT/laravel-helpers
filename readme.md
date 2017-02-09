# Laravel Helpers 

This plugin provide set of helpers functions, services and traits. 

## Instalation

### Composer
 1. Add to required `"ronasit/laravel-helpers": "master-dev"`
 1. Run `composer update`

## Functions

### array_get_list($array, $path)
This function designed to get list of all values witch placed in $path in $array.
**Example**
````
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
    
    $result = array_get_list($data, 'nested_values.*.next_level_of_nesting.*.value');
    // $result = ['h', 'e', 'l', 'l', 'o'];
````

### array_lists($array, $key)
This function designed to get value of key from every item in list and return list of them

**Example**  
````
    $data = [
        [
            'id' => 123,
            'value' => 'qwefasre'
        ],
        [
            'id' => 456,
            'value' => 'adfsdfgsdf'
        ],
        [
            'id' => 789,
            'value' => 'xzxcvzxzx'
        ]
    ];
    
    $result = array_lists($data, 'id); // $result = [123, 456, 789]
````

It is very helpful for getting list of ids or another fields from Eloquent response as example.

### array_subtraction($array1, $array2)
Return subtraction of two arrays

**Example**
````
    $array1 = [1, 2, 3];
    $array2 = [1, 2];
    
    $result = array_subtraction($array1, $array2); // $result = [3];
````

### array_equals($array1, $array2)
Check equivalency of two arrays

### array_round($array)
Round all values in list of floats. It designed just for density.

````
    $result = array([1.4, 2.9, 1.534]); // $result = [1, 3, 2];
````

### isAssociative($array)
Verifies whether an associative array or a list

````
    $associative = [
        'key' => 'value'
    ];
    
    $list = ['some', 'values'];
    
    isAssociative($associative); // true
    isAssociative($list); // false

````

### elseChain(...$callbacks)
This feature is designed to get the first non-empty function result. It does not make sense in php7, 
but can be useful when developing applications on php5.  
**Example**
````
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

### getGUID()
Generate GUID

### toZip($zip)
American post-codes has format of fives digits. But if you store it in integer format will be helpful
to have method to add missing zeros in beginning on zip-string. As example 123 will be 00123

````
    $zip = toZip(123); // $zip = 00123
````

## Traits

### FixturesTrait
This traits designed for make testing understandable and cleaner.
All auxiliary data such as the results of the operation can be placed in a way corresponding 
to the following mask **base_path("tests/fixtures/{$testClassName}/{$fixture}")**, 
and is easily obtained by the method *$this->getFixture($fixture)* or through *$this->getJsonFixture($fixture)*, 
in which will be performed automatically decode json-data.
Also you can tune your TestCase for restore dump of database witch will be places in 
**base_path("tests/fixtures/{$testClassName}/dump.sql")** and method for comfortable getting of Json-responses
It contains following methods:
 - loadTestDump()
 - getFixturePath($fixture)
 - getFixture($fixture)
 - getJsonFixture($fixture)
 - getJsonResponse()
 - assertEqualsFixture($fixture, $data)

## Services

### EntityService
This service designer for easily creation of services with control any entity.

##Tests
Automatic generation of test is supported only for PostgreSQL.

## License

Laravel Helpers plugin is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
