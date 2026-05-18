<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class BinaryCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return is_null($value) ? $value : bin2hex($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return is_null($value) ? $value : md5($value, true);
    }
}
