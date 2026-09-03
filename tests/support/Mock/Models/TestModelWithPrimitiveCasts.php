<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;

class TestModelWithPrimitiveCasts extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'score',
        'balance',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'score' => 'integer',
        'balance' => 'decimal:2',
        'published_at' => 'datetime',
    ];
}
