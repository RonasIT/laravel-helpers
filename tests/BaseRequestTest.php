<?php

namespace RonasIT\Support\Tests;

use ReflectionClass;
use RonasIT\Support\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\TestModel;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class BaseRequestTest extends HelpersTestCase
{
    use TableTestStateMockTrait;

    public function testGetOrderableFields()
    {
        $baseRequest = new BaseRequest();
        $reflectionClass = new ReflectionClass($baseRequest);

        $method = $reflectionClass->getMethod('getOrderableFields');
        $result = $method->invoke($baseRequest, TestModel::class);

        $expectedResult = 'id,name,json_field,castable_field,*,created_at,updated_at';

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetOrderableFieldsWithAdditionalFields()
    {
        $baseRequest = new BaseRequest();
        $reflectionClass = new ReflectionClass($baseRequest);

        $method = $reflectionClass->getMethod('getOrderableFields');
        $result = $method->invoke($baseRequest, TestModel::class, ['additional_field_1', 'additional_field_2']);

        $modelAndAdditionalFields = 'id,name,json_field,castable_field,*,created_at,updated_at,additional_field_1,additional_field_2';

        $this->assertEquals($modelAndAdditionalFields, $result);
    }
}
