<?php

namespace RonasIT\Support\Tests\Support\Mock\Repositories;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;

class TestRepository extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModel::class);
    }

    public function getUser(): string
    {
        return 'Correct result';
    }

    public function getFilter(): self
    {
        return $this;
    }

    public function getModelName(): string
    {
        return $this->getEntityName();
    }
}
