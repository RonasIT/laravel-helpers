<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithoutTimestamps extends Model
{
    use ModelTrait;
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'created_at',
    ];
}
