<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Tests\Support\Mock\Casts\JSONCustomCast;
use RonasIT\Support\Traits\ModelTrait;

class TestModel extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'array_field',
        'json_field',
        'object_field',
        'collection_field',
        'castable_field',
    ];

    protected $casts = [
        // Native JSON casts
        'array_field' => 'array',
        'json_field' => 'json',
        'object_field' => 'object',
        'collection_field' => 'collection',

        // Custom CastsAttributes implementation
        'castable_field' => JSONCustomCast::class,
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}
