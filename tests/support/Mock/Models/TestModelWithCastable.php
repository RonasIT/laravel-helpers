<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\CustomCastable;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithCastable extends Model
{
    use ModelTrait;

    protected $table = 'test_model_with_castables';

    protected $fillable = [
        'name',
        'castable_field',
    ];

    protected $casts = [
        'castable_field' => CustomCastable::class,
    ];
}
