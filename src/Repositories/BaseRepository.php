<?php

namespace RonasIT\Support\Repositories;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Traits\EntityControlTrait;

/**
 * @template TModel of Model = Model
 */
class BaseRepository
{
    /** @use EntityControlTrait<TModel> */
    use EntityControlTrait;
}
