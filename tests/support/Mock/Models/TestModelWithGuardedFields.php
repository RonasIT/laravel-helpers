<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithGuardedFields extends Model
{
    use ModelTrait;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
    ];

    protected $guarded = [
        'secret_field',
    ];
}
