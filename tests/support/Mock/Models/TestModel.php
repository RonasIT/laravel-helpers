<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Tests\Support\Mock\Casts\JSONCastable;
use RonasIT\Support\Tests\Support\Mock\Casts\JSONCustomCast;
use RonasIT\Support\Traits\ModelTrait;

class TestModel extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'json_field',
        'custom_cast_field',
        'castable_field',
    ];

    protected $casts = [
        'json_field' => 'array',
        'custom_cast_field' => JSONCustomCast::class,
        'castable_field' => JSONCastable::class,
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}
