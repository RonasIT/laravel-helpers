<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;

trait FixtureMockTrait
{
    protected function mockForCachingJsonFields(): void
    {
        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock
            ->shouldReceive('getColumnListing')
            ->andReturn(['id', 'json_column'])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'json_column')
            ->andReturn('json');
    }

    protected function mockForCachingWithoutJsonFields(): void
    {
        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock
            ->shouldReceive('getColumnListing')
            ->andReturn(['id', 'name'])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'name')
            ->andReturn('string');
    }

    protected function mockGettingColumnTypes(): void
    {
        Config::set('database.default', 'mysql');

        $schemeMock = Mockery::mock('overload:' . MysqlBuilder::class);
        $schemeMock
            ->shouldReceive('getColumnListing')
            ->andReturn([
                'id', 'user_id', 'title', 'text', 'description',
                'is_public', 'json_column', 'created_at', 'updated_at'
            ])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'title')
            ->andReturn('string')

            ->shouldReceive('getColumnType')
            ->with('users', 'text')
            ->andReturn('text')

            ->shouldReceive('getColumnType')
            ->with('users', 'description')
            ->andReturn('string')

            ->shouldReceive('getColumnType')
            ->with('users', 'is_public')
            ->andReturn('boolean')

            ->shouldReceive('getColumnType')
            ->with('users', 'created_at')
            ->andReturn('datetime')

            ->shouldReceive('getColumnType')
            ->with('users', 'updated_at')
            ->andReturn('datetime')

            ->shouldReceive('getColumnType')
            ->with('users', 'json_column')
            ->andReturn('json');
    }

    protected function mockGettingDataset(Collection $responseMock): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);
        $connection = $this->mockClass(Connection::class, [], true);

        DB::swap($connection);

        $builderMock
            ->expects($this->once())
            ->method('orderBy')
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($responseMock);

        $connection
            ->expects($this->once())
            ->method('table')
            ->willReturn($builderMock);
    }
}