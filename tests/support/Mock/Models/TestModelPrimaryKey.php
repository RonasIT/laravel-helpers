<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Tests\Support\Mock\Casts\JSONCustomCast;
use RonasIT\Support\Traits\ModelTrait;

class TestModelPrimaryKey extends Model
{
    use ModelTrait;
    use SoftDeletes;

    protected $primaryKey = 'name';

    protected $fillable = [
        'json_field',
        'castable_field',
    ];

    protected $casts = [
        'json_field' => 'array',
        'castable_field' => JSONCustomCast::class,
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelationModel::class);
    }
}
