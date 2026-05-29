<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;

class TestModelWithPrimitiveCasts extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'score',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'score' => 'integer',
    ];
}
