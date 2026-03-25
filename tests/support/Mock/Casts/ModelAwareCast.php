<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ModelAwareCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $model->currency . ' ' . $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        $explodedValue = explode(' ', $value);

        return $explodedValue[1];
    }
}
