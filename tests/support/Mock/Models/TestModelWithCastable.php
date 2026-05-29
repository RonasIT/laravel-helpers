<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\AddressCastable;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithCastable extends Model
{
    use ModelTrait;

    protected $table = 'test_model_with_castables';

    protected $fillable = [
        'name',
        'address',
    ];

    protected $casts = [
        'address' => AddressCastable::class,
    ];
}
