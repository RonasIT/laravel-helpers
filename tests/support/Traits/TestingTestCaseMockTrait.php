<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\ParallelTesting;

trait TestingTestCaseMockTrait
{
    protected function mockParallelTestingToken(int $token): void
    {
        ParallelTesting::resolveTokenUsing(fn () => $token);
    }
}
