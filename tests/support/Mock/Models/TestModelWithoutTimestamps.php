<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Tests\Support\Mock\Casts\UserSettingCast;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithoutTimestamps extends Model
{
    use ModelTrait;
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'json_field',
        'castable_field',
        'created_at',
    ];

    protected $casts = [
        'json_field' => 'array',
        'castable_field' => UserSettingCast::class,
    ];
}
