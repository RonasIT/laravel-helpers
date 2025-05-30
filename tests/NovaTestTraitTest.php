<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\NovaActions\TestNovaAction;
use RonasIT\Support\Tests\Support\Mock\Models\MockAuthUser;
use RonasIT\Support\Tests\Support\Mock\NovaResources\Media;
use RonasIT\Support\Tests\Support\Mock\NovaResources\TestModel;
use RonasIT\Support\Tests\Support\Mock\NovaResources\User;
use RonasIT\Support\Tests\Support\Mock\NovaResources\UserResource;
use RonasIT\Support\Testing\TestCase as PackageTestCase;
use RonasIT\Support\Traits\MockTrait;
use RonasIT\Support\Traits\NovaTestTrait;
use Symfony\Component\HttpFoundation\Response;

class NovaTestTraitTest extends TestCase
{
    use MockTrait;
    use NovaTestTrait;

    public function setUp():void
    {
        parent::setUp();

        $this
            ->getMockBuilder(PackageTestCase::class)
            ->onlyMethods(['call'])
            ->setConstructorArgs(['name'])
            ->getMock()
            ->withoutAPIVersion();
    }

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

    public static function getNovaResourceUriData(): array
    {
        return [
            [
                'resource' => Media::class,
                'uri' => '/nova-api/media',
            ],
            [
                'resource' => User::class,
                'uri' => '/nova-api/users',
            ],
            [
                'resource' => UserResource::class,
                'uri' => '/nova-api/user-resources',
            ],
            [
                'resource' => TestModel::class,
                'uri' => '/nova-api/test-models',
            ],
        ];
    }

    #[DataProvider('getNovaResourceUriData')]
    public function testGetNovaResourceUri(string $resource, string $uri): void
    {
        $result = $this->generateNovaUri($resource);

        $this->assertEquals($result, $uri);
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
