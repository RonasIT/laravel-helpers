<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RonasIT\Support\Traits\ModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModel extends Model
{
    use ModelTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'json_field',
        'castable_field'
    ];

    protected $casts = [
        'json_field' => 'array',
        'castable_field' => JSONCustomCast::class
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}