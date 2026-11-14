<?php

namespace RonasIT\Support\Tests\Support\Mock\Services;

use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;

class UntypedTestService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(TestRepository::class);
    }
}
