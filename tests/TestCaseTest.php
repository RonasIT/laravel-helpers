<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;
use RonasIT\Support\Tests\Support\Traits\TestingTestCaseMockTrait;

class TestCaseTest extends TestCase
{
    use TestingTestCaseMockTrait;

    public static function getConfigureRedisData(): array
    {
        return [
            [
                'token' => 2,
                'expected' => 2,
            ],
            [
                'token' => 16,
                'expected' => 16,
            ],
            [
                'token' => 17,
                'expected' => 1,
            ],
            [
                'token' => 48,
                'expected' => 16,
            ],
            [
                'token' => 51,
                'expected' => 3,
            ],
            [
                'token' => 'string',
                'expected' => 16,
            ],
        ];
    }

    #[DataProvider('getConfigureRedisData')]
    public function testTestCaseConfigureRedis(mixed $token, int $expected): void
    {
        $this->mockParallelTestingToken($token);

        $this->callEncapsulatedMethod(new SomeTestCase(), 'configureRedis');

        $this->assertEqualsCanonicalizing($expected, config('database.redis.default.database'));
    }
}
