<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class JSONCustomParameterizedCast implements CastsAttributes
{
    public function __construct(protected int $decimals)
    {
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return number_format((int) $value / (10 ** $this->decimals), $this->decimals);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return (int) round($value * (10 ** $this->decimals));
    }
}
