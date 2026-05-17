<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;

trait FixturesTestTrait
{
    public function mockLaravelVersion(string $version): void
    {
        $app = new readonly class($version)
        {
            public function __construct(private string $version)
            {
            }

            public function version(): string
            {
                return $this->version;
            }
        };

        $this->mockNativeFunction('RonasIT\Support\Traits', $this->functionCall('app', [], $app));
    }

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
