<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class JSONCustomCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }
}
