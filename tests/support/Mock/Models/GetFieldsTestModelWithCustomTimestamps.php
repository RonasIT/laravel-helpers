<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class GetFieldsTestModelWithCustomTimestamps extends Model
{
    use ModelTrait;

    public const string CREATED_AT = 'creation_date';
    public const string UPDATED_AT = 'updated_date';

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'creation_date',
    ];
}
