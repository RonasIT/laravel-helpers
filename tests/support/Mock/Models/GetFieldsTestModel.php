<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class GetFieldsTestModel extends Model
{
    use ModelTrait;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'json_field',
    ];
}
