<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModelNoPrimaryKey extends Model
{
    use ModelTrait, SoftDeletes;

    protected $primaryKey = null;

    protected $fillable = [
        'name',
        'json_field',
        'castable_field'
    ];
}