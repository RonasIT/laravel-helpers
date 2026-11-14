<?php

namespace RonasIT\Support\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Services\TypedTestService;
use RonasIT\Support\Tests\Support\Request\RouteEntityTestRequest;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseRequestRouteEntityTest extends TestCase
{
    use SqlMockTrait;

    protected const string SELECT_ENTITY_QUERY = 'select * from "test_models" '
        . 'where "id" = ? and "test_models"."deleted_at" is null limit 1';

    protected const string SELECT_RELATION_QUERY = 'select * from "relation_models" '
        . 'where "relation_models"."test_model_id" in (1)';

    protected array $entityQueries = [];

    public function setUp(): void
    {
        parent::setUp();

        DB::listen(function (QueryExecuted $query) {
            if (Str::contains($query->sql, 'from "test_models"')) {
                $this->entityQueries[] = $query->sql;
            }
        });
    }

    public function testLoadExistingEntity()
    {
        $this->mockSelectById(self::SELECT_ENTITY_QUERY, $this->getJsonFixture('select_query_result'), ['1']);
        $this->mockSelect(self::SELECT_RELATION_QUERY);

        $request = $this->makeRequest('v1/test/{id}', 'v1/test/1');

        $request->validateResolved();

        $testModel = $request->getTestModel();

        $this->assertEquals(1, $testModel->getKey());
        $this->assertTrue($testModel->relationLoaded('relation'));
        $this->assertTrue($request->isAuthorizeCalled);
        $this->assertCount(1, $this->entityQueries);
    }

    public function testLoadEntityWithEmptyRelations()
    {
        $this->mockSelectById(self::SELECT_ENTITY_QUERY, $this->getJsonFixture('select_query_result'), ['1']);

        $request = $this->makeRequest('v1/test/{id}', 'v1/test/1');

        $request->relations = [];

        $request->validateResolved();

        $this->assertFalse($request->getTestModel()->relationLoaded('relation'));
        $this->assertCount(1, $this->entityQueries);
    }

    public static function getLoadMissingEntityData(): array
    {
        return [
            [
                'routeUri' => 'v1/test/{id}',
                'uri' => 'v1/test/1',
                'routeParam' => 'id',
                'entityName' => null,
                'isTranslated' => false,
                'bindings' => ['1'],
                'message' => 'TestModel does not exist.',
            ],
            [
                'routeUri' => 'v1/test/{id}',
                'uri' => 'v1/test/1',
                'routeParam' => 'id',
                'entityName' => 'Event',
                'isTranslated' => false,
                'bindings' => ['1'],
                'message' => 'Event does not exist.',
            ],
            [
                'routeUri' => 'v1/test/{test_model_id}',
                'uri' => 'v1/test/5',
                'routeParam' => 'test_model_id',
                'entityName' => 'Test model',
                'isTranslated' => false,
                'bindings' => ['5'],
                'message' => 'Test model does not exist.',
            ],
            [
                'routeUri' => 'v1/test/{id}',
                'uri' => 'v1/test/1',
                'routeParam' => 'id',
                'entityName' => null,
                'isTranslated' => true,
                'bindings' => ['1'],
                'message' => 'Sorry, TestModel is gone.',
            ],
        ];
    }

    #[DataProvider('getLoadMissingEntityData')]
    public function testLoadMissingEntity(
        string $routeUri,
        string $uri,
        string $routeParam,
        ?string $entityName,
        bool $isTranslated,
        array $bindings,
        string $message,
    ) {
        if ($isTranslated) {
            $this->app['translator']->addJsonPath($this->getFixturePath('lang'));
        }

        $this->mockSelectById(self::SELECT_ENTITY_QUERY, [], $bindings);

        $request = $this->makeRequest($routeUri, $uri);

        $request->routeParam = $routeParam;
        $request->entityName = $entityName;

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage($message);

        $request->validateResolved();
    }

    public function testLoadEntityWithoutRouteParam()
    {
        $request = $this->makeRequest('v1/test/{test_model_id}', 'v1/test/1');

        try {
            $request->validateResolved();

            $this->fail('NotFoundHttpException was not thrown.');
        } catch (NotFoundHttpException $exception) {
            $this->assertEquals('TestModel does not exist.', $exception->getMessage());
        }

        $this->assertEmpty($this->entityQueries);
    }

    public function testLoadMissingEntityWhenUnauthorized()
    {
        $this->mockSelectById(self::SELECT_ENTITY_QUERY, [], ['1']);

        $request = $this->makeRequest('v1/test/{id}', 'v1/test/1');

        $request->isAuthorized = false;

        try {
            $request->validateResolved();

            $this->fail('NotFoundHttpException was not thrown.');
        } catch (NotFoundHttpException) {
            $this->assertFalse($request->isAuthorizeCalled);
        }
    }

    public function testLoadExistingEntityWhenUnauthorized()
    {
        $this->mockSelectById(self::SELECT_ENTITY_QUERY, $this->getJsonFixture('select_query_result'), ['1']);
        $this->mockSelect(self::SELECT_RELATION_QUERY);

        $request = $this->makeRequest('v1/test/{id}', 'v1/test/1');

        $request->isAuthorized = false;

        try {
            $request->validateResolved();

            $this->fail('AuthorizationException was not thrown.');
        } catch (AuthorizationException) {
            $this->assertTrue($request->isAuthorizeCalled);
        }

        $this->assertCount(1, $this->entityQueries);
    }

    public function testRelationsDoNotLeakIntoNextServiceCall()
    {
        $service = new TypedTestService();

        $this->app->instance(TypedTestService::class, $service);

        $this->mockSelectById(self::SELECT_ENTITY_QUERY, $this->getJsonFixture('select_query_result'), ['1']);
        $this->mockSelect(self::SELECT_RELATION_QUERY);
        $this->mockSelectById(self::SELECT_ENTITY_QUERY, $this->getJsonFixture('select_query_result'));

        $request = $this->makeRequest('v1/test/{id}', 'v1/test/1');

        $request->validateResolved();

        $this->assertFalse($service->find(1)->relationLoaded('relation'));
    }

    protected function makeRequest(string $routeUri, string $uri): RouteEntityTestRequest
    {
        $request = RouteEntityTestRequest::create($uri, 'get');

        $route = (new Route('GET', $routeUri, []))->bind($request);

        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        return $request;
    }
}
