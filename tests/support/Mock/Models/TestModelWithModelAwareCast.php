<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\ModelAwareCast;

class TestModelWithModelAwareCast extends Model
{
    protected $table = 'test_model_with_model_aware_casts';

    protected $fillable = [
        'currency',
        'amount',
    ];

    protected $casts = [
        // Cast that reads another model attribute (currency) to format the value
        'amount' => ModelAwareCast::class,
    ];
}
