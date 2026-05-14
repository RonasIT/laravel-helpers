<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\JSONCustomParameterizedCast;

class TestModelWithCustomParameterizedCast extends Model
{
    protected $fillable = [
        'name',
        'castable_field',
    ];

    protected $casts = [
        'castable_field' => JSONCustomParameterizedCast::class . ':2',
    ];
}
