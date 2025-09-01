<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\ParallelTesting;
use ReflectionMethod;
use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;

class UnitTest extends TestCase
{
    public function testTestCaseConfigureRedis(): void
    {
        ParallelTesting::shouldReceive('callTearDownTestCaseCallbacks');

        ParallelTesting::shouldReceive('token')
            ->once()
            ->andReturn('some_token');

        $reflection = new ReflectionMethod(SomeTestCase::class, 'configureRedis');

        $reflection->invoke(new SomeTestCase('test'));

        $this->assertEquals('some_token', config('database.redis.default.database'));
    }
}