<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Repositories\BaseRepository;

class TestRepositoryWithDifferentTimestampNames extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModelWithDifferentTimestampNames::class);
    }
}
