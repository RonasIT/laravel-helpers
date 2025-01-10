<?php

namespace RonasIT\Support\Tests\Support\Mock\Repositories;

use RonasIT\Support\Repositories\BaseRepository;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithDifferentTimestampNames;

class TestRepositoryWithDifferentTimestampNames extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModelWithDifferentTimestampNames::class);
    }
}
