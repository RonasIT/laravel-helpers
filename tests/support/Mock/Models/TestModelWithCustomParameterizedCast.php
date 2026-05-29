<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\DecimalAmountCast;

class TestModelWithCustomParameterizedCast extends Model
{
    protected $fillable = [
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => DecimalAmountCast::class . ':2',
    ];
}
