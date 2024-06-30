<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModelWithoutJsonFields extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];
}
