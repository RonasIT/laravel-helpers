<?php

namespace RonasIT\Support\Tests\Support\Traits;

use RonasIT\Support\Tests\Support\Mock\Models\TestModel;

trait ModelTestTrait
{
    protected function createModelWithTransition(mixed $originValue, mixed $newValue, string $fieldName = 'name'): TestModel
    {
        $model = new TestModel();

        $model->forceFill([$fieldName => $originValue]);
        $model->syncOriginal();
        $model->forceFill([$fieldName => $newValue]);
        $model->syncChanges();
        $model->syncOriginal();

        return $model;
    }
}
