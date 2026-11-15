<?php

namespace RonasIT\Support\Tests\Support\Types;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;
use RonasIT\Support\Tests\Support\Mock\Repositories\TypedTestRepository;

use function PHPStan\Testing\assertType;

function typedRepository(TypedTestRepository $repository): void
{
    assertType(TestModel::class . '|null', $repository->find(1));
    assertType(TestModel::class . '|null', $repository->first(['id' => 1]));
    assertType(TestModel::class . '|null', $repository->findBy('id', 1));
    assertType(TestModel::class . '|null', $repository->last());
    assertType(TestModel::class . '|null', $repository->update(1, []));
    assertType(TestModel::class, $repository->create([]));
    assertType(TestModel::class, $repository->firstOrCreate(['id' => 1]));
    assertType(TestModel::class, $repository->updateOrCreate(1, []));
    assertType(TestModel::class . '|null', $repository->with('relation')->find(1));
    assertType(TestModel::class . '|null', $repository->withTrashed()->with('relation')->first());
}

function untypedRepository(TestRepository $repository): void
{
    assertType(Model::class . '|null', $repository->find(1));
    assertType(Model::class, $repository->create([]));
}
