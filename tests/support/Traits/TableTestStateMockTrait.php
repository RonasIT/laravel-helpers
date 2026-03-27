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

    protected function mockGettingDataset(Collection $responseMock, string $uniqueKey = 'id'): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('table')->with('test_models')->once()->andReturn($builderMock);

        $builderMock
            ->method('orderBy')
            ->with($uniqueKey)
            ->willReturnSelf();

        $builderMock
            ->method('get')
            ->willReturn($responseMock);
    }

    protected function mockGettingDatasetForChanges(Collection $responseMock, Collection $initialState, string $tableName, string $uniqueKey = 'id'): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);
        DB::shouldReceive('connection')->twice()->andReturnSelf();
        DB::shouldReceive('table')->with($tableName)->twice()->andReturn($builderMock);

        $builderMock
            ->method('orderBy')
            ->with($uniqueKey)
            ->willReturnSelf();

        $builderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($initialState, $responseMock);
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
}
