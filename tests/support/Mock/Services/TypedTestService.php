<?php

namespace RonasIT\Support\Tests\Support\Mock\Services;

use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Repositories\TypedTestRepository;

/**
 * @extends EntityService<TestModel>
 */
class TypedTestService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(TypedTestRepository::class);
    }
}
