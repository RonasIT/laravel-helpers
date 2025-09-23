<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;
use RonasIT\Support\Tests\Support\Traits\TestingTestCaseMockTrait;

class TestCaseTest extends TestCase
{
    use TestingTestCaseMockTrait;

    public function testTestCaseConfigureRedis(): void
    {
        $this->mockParallelTestingToken(1234);

        $this->callEncapsulatedMethod(new SomeTestCase(), 'configureRedis');

        $this->assertEqualsCanonicalizing(1234, config('database.redis.default.database'));
    }
}
