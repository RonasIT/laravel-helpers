<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionClass;
use RonasIT\Support\Testing\TestCase;

trait TableTestStateMockTrait
{
    use MockTestTrait;

    private const array AVAILABLE_BINARY_FIELD_TYPES = [
        'bytea',
        'blob',
        'tinyblob',
        'mediumblob',
        'longblob',
        'binary',
        'varbinary',
    ];

    protected function mockGettingDataset(Collection $responseMock, string $uniqueKey = 'id'): void
    {
        $connectionMock = $this->mockClass(Connection::class, ['getDriverName', 'getDatabaseName', 'table'], true);
        $builderMock = $this->mockClass(Builder::class, ['select', 'where', 'whereIn', 'orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);

        $connectionMock
            ->method('getDriverName')
            ->willReturn('pgsql');

        $connectionMock
            ->method('getDatabaseName')
            ->willReturn('public');

        $connectionMock
            ->expects($this->exactly(2))
            ->method('table')
            ->with($this->callback(fn ($table) => in_array($table, [
                'information_schema.columns',
                'test_models',
            ])))
            ->willReturn($builderMock);

        DB::shouldReceive('connection')->twice()->andReturn($connectionMock);

        $builderMock
            ->method('select')
            ->with('column_name')
            ->willReturnSelf();

        $builderMock
            ->method('where')
            ->with('table_name', 'test_models')
            ->willReturnSelf();

        $builderMock
            ->expects($this->once())
            ->method('orderBy')
            ->with($uniqueKey)
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(2))
            ->method('whereIn')
            ->willReturnCallback(function (string $column, array $values) use ($builderMock) {
                match ($column) {
                    'data_type' => $this->assertEquals($values, self::AVAILABLE_BINARY_FIELD_TYPES),
                    'table_schema' => $this->assertEquals($values, ['public']),
                    default => $this->fail('Unexpected call'),
                };

                return $builderMock;
            });

        $builderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($responseMock, collect());
    }

    protected function mockGettingDatasetForChanges(
        Collection $responseMock,
        Collection $initialState,
        string $tableName,
        string $uniqueKey = 'id',
        ?string $binaryColumn = null,
        string $dbDriver = 'pgsql',
    ): void {
        $connectionMock = $this->mockClass(Connection::class, ['getDriverName', 'getDatabaseName', 'table'], true);
        $builderMock = $this->mockClass(Builder::class, ['select', 'where', 'whereIn', 'orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);

        $connectionMock
            ->method('getDriverName')
            ->willReturn($dbDriver);

        $connectionMock
            ->method('getDatabaseName')
            ->willReturn('public');

        $connectionMock
            ->expects($this->exactly(3))
            ->method('table')
            ->with($this->callback(fn ($table) => in_array($table, [
                'information_schema.columns',
                $tableName,
            ])))
            ->willReturn($builderMock);

        DB::shouldReceive('connection')->times(3)->andReturn($connectionMock);

        $builderMock
            ->method('select')
            ->with('column_name')
            ->willReturnSelf();

        $builderMock
            ->method('where')
            ->with('table_name', $tableName)
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(2))
            ->method('orderBy')
            ->with($uniqueKey)
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(2))
            ->method('whereIn')
            ->willReturnCallback(function (string $column, array $values) use ($builderMock) {
                match ($column) {
                    'data_type' => $this->assertEquals(self::AVAILABLE_BINARY_FIELD_TYPES, $values),
                    'table_schema' => $this->assertEquals(['public'], $values),
                    default => $this->fail('Unexpected call'),
                };

                return $builderMock;
            });

        $builderMock
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $initialState,
                empty($binaryColumn) ? collect() : collect([['column_name' => $binaryColumn]]),
                $responseMock,
            );
    }

    protected function mockUnsupportedDriverName(): void
    {
        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);

        $connectionMock = $this->mockClass(Connection::class, ['getDriverName', 'getDatabaseName', 'table'], true);

        $connectionMock
            ->method('getDriverName')
            ->willReturn('unsupported_driver');

        $connectionMock
            ->method('getDatabaseName')
            ->willReturn('public');

        DB::shouldReceive('connection')->once()->andReturn($connectionMock);
    }

    protected function mockTestStateCreationSetGlobalExportMode(string $methodName, string $entity, bool $testCaseGlobalExportMode): bool
    {
        $testCaseMock = Mockery::mock(TestCase::class)
            ->makePartial()
            ->setGlobalExportMode($testCaseGlobalExportMode);

        $instance = $this->callEncapsulatedMethod($testCaseMock, $methodName, $entity);

        $reflectionClass = new ReflectionClass($instance);
        $globalExportMode = $reflectionClass->getProperty('globalExportMode');

        return $globalExportMode->getValue($instance);
    }

    protected function getTestResource()
    {
        $resource = fopen('php://memory', 'r+b');
        fwrite($resource, md5('some_string', true));
        rewind($resource);

        return $resource;
    }
}
