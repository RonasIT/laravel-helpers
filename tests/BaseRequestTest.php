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
        $orderableFields = $method->invoke($baseRequest, TestModel::class);

        $modelFields = implode(',', TestModel::getFields());

        $this->assertEquals($orderableFields, $modelFields);
    }

    public function testGetOrderableFieldsWithAdditionalFields()
    {
        $baseRequest = new BaseRequest();
        $reflectionClass = new ReflectionClass($baseRequest);

        $method = $reflectionClass->getMethod('getOrderableFields');
        $orderableFields = $method->invoke($baseRequest, TestModel::class, ['additional_field_1', 'additional_field_2']);

        $modelFields = implode(',', array_merge(TestModel::getFields(), ['additional_field_1', 'additional_field_2'])) ;

        $this->assertEquals($orderableFields, $modelFields);
    }
}
