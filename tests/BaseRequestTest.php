<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Http\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class BaseRequestTest extends TestCase
{
    use TableTestStateMockTrait;

    public function testGetOrderableFields()
    {
        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', TestModel::class);

        $expectedResult = 'id,name,json_field,castable_field,*,created_at,updated_at';

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetOrderableFieldsWithAdditionalFields()
    {
        $args = [
            TestModel::class,
            ['additional_field_1', 'additional_field_2'],
        ];

        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', ...$args);

        $expectedResult = 'id,name,json_field,castable_field,*,created_at,updated_at,additional_field_1,additional_field_2';

        $this->assertEquals($expectedResult, $result);
    }
}
