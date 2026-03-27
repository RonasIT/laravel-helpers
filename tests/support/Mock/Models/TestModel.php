<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Traits\ModelTrait;

class TestModel extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}
