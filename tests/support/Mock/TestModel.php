<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RonasIT\Support\Traits\ModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModel extends Model
{
    use ModelTrait, SoftDeletes;

    protected $fillable = ['name'];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}