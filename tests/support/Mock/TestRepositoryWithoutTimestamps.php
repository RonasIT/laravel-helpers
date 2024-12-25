<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Repositories\BaseRepository;

class TestRepositoryWithoutTimestamps extends BaseRepository
{
    public function __construct()
    {
        $this->setModel(TestModelWithoutTimestamps::class);
    }
}
