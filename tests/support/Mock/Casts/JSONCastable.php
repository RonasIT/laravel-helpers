<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class JSONCastable implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes {
            public function get($model, $key, $value, $attributes)
            {
                return json_decode($value, true);
            }

            public function set($model, $key, $value, $attributes)
            {
                return json_encode($value);
            }
        };
    }
}
