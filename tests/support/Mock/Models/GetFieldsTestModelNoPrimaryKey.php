<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class GetFieldsTestModelNoPrimaryKey extends Model
{
    use ModelTrait;

    protected $table = 'test_models';

    protected $primaryKey = null;

    protected $fillable = [
        'name',
        'json_field',
    ];
}
