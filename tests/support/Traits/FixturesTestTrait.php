<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;

trait FixturesTestTrait
{
    public function bindMockedDbInstance(MockObject $connection, int $connectionCallCount = 2): void
    {
        $db = $this->mockClass(DatabaseManager::class, array_fill(
            start_index: 0,
            count: $connectionCallCount,
            value: $this->functionCall('connection', [null], $connection),
        ), true);

        $this->app->instance('db', $db);
    }
}