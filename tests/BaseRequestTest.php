<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Http\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Request\UpdateTestRequest;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class BaseRequestTest extends TestCase
{
    use TableTestStateMockTrait;

    public function testGetOrderableFields()
    {
        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', TestModel::class);

        $expectedResult = 'id,name,*,created_at,updated_at';

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetOrderableFieldsWithAdditionalFields()
    {
        $args = [
            TestModel::class,
            ['additional_field_1', 'additional_field_2'],
        ];

        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', ...$args);

        $expectedResult = 'id,name,*,created_at,updated_at,additional_field_1,additional_field_2';

        $this->assertEquals($expectedResult, $result);
    }

    public static function getOnlyValidatedRequestData(): array
    {
        return [
            [
                'keys' => [],
                'result' => [
                    'name' => 'Update User',
                    'email' => 'updateuser@example.com',
                    'address' => [
                        'CA',
                        '123 Avenue',
                    ],
                    'meta' => [
                        [
                            'value' => '111',
                            'description' => 'meta id',
                        ],
                    ],
                ],
            ],
            [
                'keys' => ['name', 'email'],
                'result' => [
                    'name' => 'Update User',
                    'email' => 'updateuser@example.com',
                ],
            ],
            [
                'keys' => ['nonexistentKey'],
                'result' => [],
            ],
        ];
    }

    #[DataProvider('getOnlyValidatedRequestData')]
    public function testOnlyValidated(array $keys, array $result)
    {
        $data = $this->getJsonFixture('update_test_request');

        $request = UpdateTestRequest::create('v1/test', 'put', $data);

        $this->assertEquals($request->onlyValidated($keys), $result);
    }
}
