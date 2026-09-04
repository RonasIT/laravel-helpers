<?php

namespace RonasIT\Support\Tests;

use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Http\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\Handlers\TestRequestHandler;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Request\UpdateTestRequest;
use RonasIT\Support\Tests\Support\Request\ValidateResolvedTestRequest;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class BaseRequestTest extends TestCase
{
    use TableTestStateMockTrait;

    public function testGetOrderableFields()
    {
        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', TestModel::class);

        $expectedResult = 'id,name,json_field,custom_cast_field,castable_field,created_at,updated_at';

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetOrderableFieldsWithAdditionalFields()
    {
        $args = [
            TestModel::class,
            ['additional_field_1', 'additional_field_2'],
        ];

        $result = $this->callEncapsulatedMethod(new BaseRequest(), 'getOrderableFields', ...$args);

        $expectedResult = 'id,name,json_field,custom_cast_field,castable_field,created_at,updated_at,additional_field_1,additional_field_2';

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

    public function testValidateResolved()
    {
        $request = ValidateResolvedTestRequest::create('v1/test', 'get');

        $request->setContainer($this->app);

        $request->validateResolved();

        $this->assertEquals(['init', 'beforeAuthorization'], $request->calls);
    }

    public function testValidateResolvedFailedValidation()
    {
        $request = ValidateResolvedTestRequest::create('v1/test', 'get');

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $request->validationRules = ['name' => 'required'];

        $this->expectException(ValidationException::class);

        $request->validateResolved();
    }

    public function testValidateResolvedCallsBeforeAuthorizationHandlers()
    {
        TestRequestHandler::$calls = [];

        $request = ValidateResolvedTestRequest::create('v1/test', 'get');

        $request->setContainer($this->app);

        $request->beforeAuthorizationHandlers = [
            new TestRequestHandler('first'),
            new TestRequestHandler('second'),
        ];

        $request->validateResolved();

        $this->assertEquals(['first', 'second'], TestRequestHandler::$calls);
    }

    public function testBeforeAuthorizationDefault()
    {
        $request = new BaseRequest();

        $result = $this->callEncapsulatedMethod($request, 'beforeAuthorization');

        $this->assertEquals([], $result);
    }

    #[DataProvider('getOnlyValidatedRequestData')]
    public function testOnlyValidated(array $keys, array $result)
    {
        $data = $this->getJsonFixture('update_test_request');

        $request = UpdateTestRequest::create('v1/test', 'put', $data);

        $this->assertEquals($request->onlyValidated($keys), $result);
    }
}
