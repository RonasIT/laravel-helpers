<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ModelTestState extends BaseTestState
{
    protected Model $model;

    public function __construct(string $modelClassName, ?string $connectionName = null)
    {
        $this->model = new $modelClassName();

        parent::__construct(
            tableName: $this->model->getTable(),
            jsonFields: $this->getModelJSONFields(),
            connectionName: $connectionName ?? $this->model->getConnectionName(),
        );
    }

    protected function getModelJSONFields(): array
    {
        $casts = $this->model->getCasts();

        $jsonCasts = array_filter($casts, fn ($cast) => $this->isJsonCast($cast));

        return array_keys($jsonCasts);
    }

    protected function isJsonCast(string $cast): bool
    {
        return ($cast === 'array') || (class_exists($cast) && is_subclass_of($cast, CastsAttributes::class));
    }
}
