<?php

namespace RonasIT\Support\Tests\Support\Types;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Services\TypedTestService;
use RonasIT\Support\Tests\Support\Mock\Services\UntypedTestService;

use function PHPStan\Testing\assertType;

function typedService(TypedTestService $service): void
{
    assertType(TestModel::class . '|null', $service->find(1));
    assertType(TestModel::class . '|null', $service->first(['id' => 1]));
    assertType(TestModel::class . '|null', $service->findBy('id', 1));
    assertType(TestModel::class . '|null', $service->last());
    assertType(TestModel::class . '|null', $service->update(1, []));
    assertType(TestModel::class, $service->create([]));
    assertType(TestModel::class, $service->firstOrCreate(['id' => 1]));
    assertType(TestModel::class, $service->updateOrCreate(1, []));
    assertType(TestModel::class . '|null', $service->with('relation')->find(1));
    assertType(TestModel::class . '|null', $service->withTrashed()->with('relation')->first());
}

function untypedService(UntypedTestService $service): void
{
    assertType(Model::class . '|null', $service->find(1));
}
