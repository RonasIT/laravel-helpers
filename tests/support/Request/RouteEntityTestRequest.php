<?php

namespace RonasIT\Support\Tests\Support\Request;

use RonasIT\Support\Http\BaseRequest;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Services\TypedTestService;

class RouteEntityTestRequest extends BaseRequest
{
    public string $routeParam = 'id';
    /** @var list<string> */
    public array $relations = ['relation'];
    public ?string $entityName = null;
    public bool $isAuthorized = true;
    public bool $isAuthorizeCalled = false;

    protected TestModel $testModel;

    public function authorize(): bool
    {
        $this->isAuthorizeCalled = true;

        return $this->isAuthorized;
    }

    public function getTestModel(): TestModel
    {
        return $this->testModel;
    }

    protected function init(): void
    {
        $this->testModel = $this->loadRouteEntity(TypedTestService::class, $this->routeParam, $this->relations, $this->entityName);
    }
}
