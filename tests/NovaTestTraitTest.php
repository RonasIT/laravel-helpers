<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RonasIT\Support\Tests\Support\Mock\NovaActions\TestNovaAction;
use RonasIT\Support\Tests\Support\Mock\Models\MockAuthUser;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Traits\MockTrait;
use RonasIT\Support\Traits\NovaTestTrait;
use Symfony\Component\HttpFoundation\Response;

class NovaTestTraitTest extends TestCase
{
    use MockTrait;
    use NovaTestTrait;

    public function testMockSingleCall()
    {
        $result = $this->novaSearchParams([
            'Badge:kyc_status' => ['Completed'],
        ]);

        $this->assertEquals($result, [
            'search' => '',
            'filters' => 'eyJCYWRnZTpreWNfc3RhdHVzIjpbIkNvbXBsZXRlZCJdfQ==',
            'perPage' => 25,
        ]);
    }

    public function testGetNovaResourceUri()
    {
        $result = $this->generateNovaUri(TestModel::class);

        $this->assertEquals($result, '/nova-api/test-models');
    }

    public function testNovaCreateResource()
    {
        Route::post('/nova-api/test-models', function (Request $request) {
            $request->validate([
                'key' => 'required|string',
            ]);

            return response('', Response::HTTP_CREATED);
        });

        $result = $this->novaCreateResourceAPICall(TestModel::class, ['key' => 'value']);

        $result->assertCreated();
    }

    public function testNovaUpdateResource()
    {
        Route::put('/nova-api/test-models/1', function (Request $request) {
            $request->validate([
                'key' => 'required|string',
            ]);

            return response($request->all(), Response::HTTP_OK);
        });

        $result = $this->novaUpdateResourceAPICall(TestModel::class, 1, ['key' => 'value']);

        $result->assertOk();
    }

    public function testNovaGetResource()
    {
        Route::get('/nova-api/test-models/1', function () {
            return response('', Response::HTTP_OK);
        });

        $result = $this->novaGetResourceAPICall(TestModel::class, 1);

        $result->assertOk();
    }

    public function testNovaSearchResource()
    {
        Route::get('/nova-api/test-models', function (Request $request) {
            $request->validate([
                'key' => 'required|string',
            ]);

            return response($request->all(), Response::HTTP_OK);
        });

        $result = $this->novaSearchResourceAPICall(TestModel::class,  ['key' => 'value']);

        $result->assertOk();
    }

    public function testNovaGetCreationFields()
    {
        Route::get('/nova-api/test-models/creation-fields', function (Request $request) {
            return response('', Response::HTTP_OK);
        });

        $result = $this->novaGetCreationFieldsAPICall(TestModel::class);

        $result->assertOk();
    }

    public function testNovaGetActions()
    {
        Route::get('/nova-api/test-models/actions', function (Request $request) {
            $request->validate([
                'resources' => 'required|string',
            ]);

            return response($request->all(), Response::HTTP_OK);
        });

        $result = $this->novaGetActionsAPICall(TestModel::class, [1, 2]);

        $result->assertOk();

        $result->assertContent('{"resources":"1,2"}');
    }

    public function testNovaDeleteResource()
    {
        Route::delete('/nova-api/test-models', function (Request $request) {
            $request->validate([
                'resources' => 'required|array',
            ]);

            return response($request->all(), Response::HTTP_OK);
        });

        $result = $this->novaDeleteResourceAPICall(TestModel::class, [1, 2]);

        $result->assertOk();

        $result->assertContent('{"resources":[1,2]}');
    }

    public function testNovaGetUpdatableFields()
    {
        Route::get('/nova-api/test-models/1/update-fields', function (Request $request) {
            return response('', Response::HTTP_OK);
        });

        $result = $this->novaGetUpdatableFieldsAPICall(TestModel::class, 1);

        $result->assertOk();
    }

    public function testNovaRunAction()
    {
        Route::post('/nova-api/test-models/action', function (Request $request) {
            return response($request->all(), Response::HTTP_OK);
        });

        $result = $this->novaRunActionAPICall(TestModel::class, TestNovaAction::class, ['key' => 'value']);

        $result->assertOk();

        $result->assertContent('{"key":"value","action":"test-nova-action"}');
    }

    public function testNovaActingAs()
    {
        $mockedUser = new MockAuthUser();

        $this->novaActingAs($mockedUser);

        $this->assertEquals($mockedUser, Auth::user());
    }

    public function testNovaActingAsUserNotSet()
    {
        $result = $this->novaActingAs();

        $this->assertEquals(true, ($result instanceof self));
    }
}
