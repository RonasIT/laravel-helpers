<?php

namespace RonasIT\Support\Tests;

use ReflectionMethod;
use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;
use RonasIT\Support\Tests\Support\Traits\TestingTestCaseMockTrait;

class TestCaseTest extends TestCase
{
    use TestingTestCaseMockTrait;

    public function testTestCaseConfigureRedis(): void
    {
        $this->mockParallelTestingToken('some_token');

        $reflection = new ReflectionMethod(SomeTestCase::class, 'configureRedis');

        $reflection->invoke(new SomeTestCase());

        $this->assertEquals('some_token', config('database.redis.default.database'));
    }
}
