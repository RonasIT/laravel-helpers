<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\UserSettingCast;

class TestModelWithCustomJsonCast extends Model
{
    protected $fillable = [
        'name',
        'settings',
    ];

    protected $casts = [
        'settings' => UserSettingCast::class,
    ];
}
