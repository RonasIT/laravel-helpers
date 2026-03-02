<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Testing\SomeTestCase;

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

    public function testTestCasePrepareMysqlDB(): void
    {
        $tables = [
            [
                'name' => 'users',
            ],
            [
                'name' => 'groups',
            ],
        ];

        Config::set('database.default', 'mysql');

        $mock = Mockery::mock(SomeTestCase::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('getTables')->once()->andReturn($tables);
        $mock->shouldReceive('resetMySQLAutoIncrement')->once()->with($tables);

        $this->callEncapsulatedMethod($mock, 'prepareDB');

        $mock->shouldNotReceive('prepareSequences');
    }

    public function testTestCasePreparePgsqlDB(): void
    {
        Config::set('database.default', 'pgsql');

        $mock = Mockery::mock(SomeTestCase::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('prepareSequences')->once();

        $this->callEncapsulatedMethod($mock, 'prepareDB');

        $mock->shouldNotReceive('resetMySQLAutoIncrement');
    }
}
