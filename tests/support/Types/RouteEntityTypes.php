<?php

namespace RonasIT\Support\Tests\Support\Types;

use RonasIT\Support\Http\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Services\TypedTestService;
use RonasIT\Support\Tests\Support\Request\RouteEntityTestRequest;

use function PHPStan\Testing\assertType;

class RouteEntityTypesRequest extends BaseRequest
{
    protected function init(): void
    {
        $entity = $this->loadRouteEntity(TypedTestService::class, 'test_model_id', ['relation'], 'Test model');

        assertType(TestModel::class, $entity);
        assertType(TestModel::class, $this->loadRouteEntity(TypedTestService::class));
    }
}

function routeEntity(RouteEntityTestRequest $request): TestModel
{
    assertType(TestModel::class, $request->getTestModel());

    return $request->getTestModel()->load('relation');
}
