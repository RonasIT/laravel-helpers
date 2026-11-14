<?php

namespace RonasIT\Support\Tests\Support\Mock\Repositories;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;

/**
 * @extends BaseRepository<TestModel>
 */
class TypedTestRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModel::class);
    }
}
