<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\ParallelTesting;

trait TestingTestCaseMockTrait
{
    protected function mockParallelTestingToken(string $token): void
    {
        ParallelTesting::shouldReceive('callTearDownTestCaseCallbacks');

        ParallelTesting::shouldReceive('token')
            ->once()
            ->andReturn($token);
    }
}
