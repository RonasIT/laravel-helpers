<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;

class TestModelWithAllNativeJsonCasts extends Model
{
    protected $fillable = [
        'name',
        'array_field',
        'json_field',
        'object_field',
        'collection_field',
    ];

    protected $casts = [
        'array_field' => 'array',
        'json_field' => 'json',
        'object_field' => 'object',
        'collection_field' => 'collection',
    ];
}
