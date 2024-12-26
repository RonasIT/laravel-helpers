<?php

namespace RonasIT\Support\Tests\Support\Mock;

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
