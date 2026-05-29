<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\CurrencyFormattedCast;

class TestModelWithCrossAttributeCast extends Model
{
    protected $fillable = [
        'currency',
        'amount',
    ];

    protected $casts = [
        'amount' => CurrencyFormattedCast::class,
    ];
}
