<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Tests\Support\Mock\Casts\UserSettingCast;
use RonasIT\Support\Tests\Support\Mock\DTO\Address;

class TestModelWithCustomJsonCast extends Model
{
    protected $fillable = [
        'name',
        'settings',
        'addresses',
    ];

    protected $casts = [
        'settings' => UserSettingCast::class,
        'addresses' => AsCollection::class . ':' . Address::class,
    ];
}
