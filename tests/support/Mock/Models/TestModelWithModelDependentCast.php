<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\CurrencyFormattedCast;

class TestModelWithModelDependentCast extends Model
{
    protected $table = 'test_model_with_model_dependent_casts';

    protected $fillable = [
        'currency',
        'amount',
    ];

    protected $casts = [
        'amount' => CurrencyFormattedCast::class,
    ];
}
