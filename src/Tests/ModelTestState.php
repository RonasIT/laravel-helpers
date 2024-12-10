<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ModelTestState extends TableTestState
{
    public function __construct(string $modelClassName)
    {
        $model = new $modelClassName();

        parent::__construct(
            tableName: $model->getTable(),
            jsonFields: $this->getModelJSONFields($model),
            connectionName: $model->getConnectionName($model),
        );
    }

    protected function getModelJSONFields(Model $model): array
    {
        $casts = $model->getCasts();

        $jsonCasts = array_filter($casts, fn ($cast) => $this->isJsonCast($cast));

        return array_keys($jsonCasts);
    }

    protected function isJsonCast(string $cast): bool
    {
        return ($cast === 'array') || (class_exists($cast) && is_subclass_of($cast, CastsAttributes::class));
    }
}
