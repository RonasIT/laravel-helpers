<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionClass;
use RonasIT\Support\Testing\TestCase;

trait TableTestStateMockTrait
{
    use MockTestTrait;

    protected function mockGettingDataset(Collection $responseMock): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('table')->with('test_models')->once()->andReturn($builderMock);

        $builderMock
            ->method('orderBy')
            ->with('id')
            ->willReturnSelf();

        $builderMock
            ->method('get')
            ->willReturn($responseMock);
    }

    protected function mockDBConnection(int $times): void
    {
        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);
        DB::shouldReceive('connection')->times($times)->andReturnSelf();
    }

    protected function mockGettingDatasetForChanges(Collection $responseMock, Collection $initialState, string $tableName): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

        DB::shouldReceive('table')->with($tableName)->twice()->andReturn($builderMock);

        $builderMock
            ->method('orderBy')
            ->with('id')
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($initialState, $responseMock);
    }

    protected function mockGettingBinaryColumns(Collection|string $resultFixture, string $tableName): void
    {
        if (is_string($resultFixture)) {
            $resultFixture = collect($this->getJsonFixture($resultFixture));
        }

        $builderMock = $this->mockClass(Builder::class, ['select', 'where', 'whereIn', 'get'], true);

        DB::shouldReceive('table')
            ->with('information_schema.columns')
            ->once()
            ->andReturn($builderMock);

        $builderMock
            ->method('select')
            ->with('column_name')
            ->willReturnSelf();

        $builderMock
            ->method('where')
            ->with('table_name', $tableName)
            ->willReturnSelf();

        $builderMock
            ->method('whereIn')
            ->with(
                'data_type',
                [
                    'bytea',
                    'blob',
                    'tinyblob',
                    'mediumblob',
                    'longblob',
                    'binary',
                    'varbinary',
                ],
            )
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(1))
            ->method('get')
            ->willReturn($resultFixture);
    }

    protected function mockTestStateCreationSetGlobalExportMode(string $methodName, string $entity, bool $testCaseGlobalExportMode): bool
    {
        $testCaseMock = $this
            ->getMockBuilder(TestCase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $testCaseMock->setGlobalExportMode($testCaseGlobalExportMode);

        $instance = $this->callEncapsulatedMethod($testCaseMock, $methodName, $entity);

        $reflectionClass = new ReflectionClass($instance);
        $globalExportMode = $reflectionClass->getProperty('globalExportMode');

        return $globalExportMode->getValue($instance);
    }
}
