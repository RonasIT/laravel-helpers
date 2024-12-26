<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithDifferentTimestampNames extends Model
{
    use ModelTrait;
    use SoftDeletes;

    public const string CREATED_AT = 'creation_date';
    public const string UPDATED_AT = 'updated_date';

    public $timestamps = true;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'json_field',
        'castable_field',
        'creation_date',
    ];

    protected $casts = [
        'json_field' => 'array',
        'castable_field' => JSONCustomCast::class,
    ];
}
