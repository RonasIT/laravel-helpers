<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class GetFieldsTestModelWithoutTimestamps extends Model
{
    use ModelTrait;

    public $timestamps = false;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'json_field',
    ];
}
