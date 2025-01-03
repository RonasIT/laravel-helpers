<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithoutJsonFields extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];
}
