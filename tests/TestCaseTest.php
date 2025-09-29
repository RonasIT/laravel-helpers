<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;
use RonasIT\Support\Tests\Support\Traits\MockTestTrait;

class TestCaseTest extends TestCase
{
    public static function getConfigureRedisData(): array
    {
        return [
            [
                'token' => 0,
                'expected' => 0,
            ],
            [
                'token' => 15,
                'expected' => 15,
            ],
            [
                'token' => 16,
                'expected' => 0,
            ],
            [
                'token' => 17,
                'expected' => 1,
            ],
            [
                'token' => 'string',
                'expected' => 0,
            ],
        ];
    }

    #[DataProvider('getConfigureRedisData')]
    public function testTestCaseConfigureRedis(mixed $token, int $expected): void
    {
        $this->mockParallelTestingToken($token);

        $this->callEncapsulatedMethod(new SomeTestCase(), 'configureRedis');

        $this->assertEquals($expected, config('database.redis.default.database'));
    }
}
