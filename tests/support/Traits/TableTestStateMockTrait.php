<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionClass;
use RonasIT\Support\Testing\TestCase;
use RonasIT\Support\Traits\MockTrait;

trait TableTestStateMockTrait
{
    use MockTrait;
    use SqlMockTrait;

    private const array SUPPORTED_DB_DRIVERS = [
        'pgsql',
        'mysql',
    ];

    private const array AVAILABLE_BINARY_FIELD_TYPES = [
        'bytea',
        'blob',
        'tinyblob',
        'mediumblob',
        'longblob',
        'binary',
        'varbinary',
    ];

    protected function mockGettingDataset(Collection $responseMock, $tableName = 'test_models', $uniqueKey = 'id'): void
    {
        $this->mockSelect(
            query: "select * from \"{$tableName}\" order by \"{$uniqueKey}\" asc",
            result: $responseMock->toArray(),
        );
    }

    protected function mockGettingDatasetForChanges(
        Collection $changedDataset,
        Collection $initialState,
        string $tableName,
        string $uniqueKey = 'id',
        ?string $binaryColumn = null,
        string $dbDriver = 'pgsql',
    ): void {
        $this->mockGettingDataset($initialState, $tableName, $uniqueKey);

        $this->mockGetConnectionDriver($dbDriver);

        if (in_array($dbDriver, self::SUPPORTED_DB_DRIVERS)) {
            $this->mockGetBinaryColumns($dbDriver, $tableName, $binaryColumn);
        }

        $this->mockGettingDataset($changedDataset, $tableName, $uniqueKey);
    }

    protected function mockGetConnectionDriver(string $dbDriver): void
    {
        $realConnection = DB::connection();

        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDriverName', 'getDatabaseName', 'table'])
            ->getMock();

        $connectionMock
            ->method('getDriverName')
            ->willReturn($dbDriver);

        $connectionMock
            ->method('getDatabaseName')
            ->willReturn($realConnection->getDatabaseName());

        $connectionMock
            ->method('table')
            ->willReturnCallback(fn ($table, $as = null) => $realConnection->table($table, $as));

        DB::shouldReceive('getDefaultConnection')->once()->andReturn($realConnection->getName());
        DB::shouldReceive('connection')->times(3)->andReturn($connectionMock);
    }

    protected function mockGetBinaryColumns(string $dbDriver, string $tableName, ?string $binaryColumn = null): void
    {
        $tableSchema = match ($dbDriver) {
            'mysql' => ':memory:',
            default => 'public',
        };

        $this->mockSelect(
            query: 'select "column_name" '
            . 'from "information_schema"."columns" '
            . 'where "table_name" = ? '
            . 'and "table_schema" in (?) '
            . 'and "data_type" in (?, ?, ?, ?, ?, ?, ?)',
            result: (empty($binaryColumn))
                ? []
                : [['column_name' => $binaryColumn]],
            bindings: [
                $tableName,
                $tableSchema,
                ...self::AVAILABLE_BINARY_FIELD_TYPES,
            ],
        );
    }

    protected function mockTestStateCreationSetGlobalExportMode(
        string $methodName,
        string $entity,
        bool $testCaseGlobalExportMode,
    ): bool {
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
