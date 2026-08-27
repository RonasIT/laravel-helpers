<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\PlainStringCast;

class TestModelWithCustomNonJsonCast extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => PlainStringCast::class,
    ];
}
