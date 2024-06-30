<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Repositories\BaseRepository;

class TestRepositoryNoPrimaryKey extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModelNoPrimaryKey::class);
    }
}
