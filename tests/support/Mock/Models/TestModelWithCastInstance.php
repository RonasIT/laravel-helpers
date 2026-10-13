<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\StringableJSONCast;

class TestModelWithCastInstance extends Model
{
    protected $fillable = [
        'name',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => new StringableJSONCast(),
        ];
    }
}
