<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Traits\ModelTrait;

class TestModelNoPrimaryKey extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $primaryKey = null;

    protected $fillable = [
        'name',
        'json_field',
        'castable_field',
    ];
}
