<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationModel extends Model
{
    public function child_relation(): HasMany
    {
        return $this->hasMany(ChildRelationModel::class);
    }
}
