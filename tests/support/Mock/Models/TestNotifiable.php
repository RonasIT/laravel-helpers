<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TestNotifiable extends Model
{
    use Notifiable;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $attributes = [
        'id' => 1,
        'name' => 'John Doe',
    ];
}
