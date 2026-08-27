<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\ModelTrait;

class TestModelWithGuardedWildcard extends Model
{
    use ModelTrait;

    protected $table = 'test_models';

    protected $guarded = ['*'];
}
