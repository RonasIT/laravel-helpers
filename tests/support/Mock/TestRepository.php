<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Repositories\BaseRepository;

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
}