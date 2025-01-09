<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use RonasIT\Support\Tests\TableTestState;
use RonasIT\Support\Tests\TestCase;

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

    protected function mockGettingDatasetForChanges(Collection $responseMock, Collection $initialState, string $tableName): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

        DB::shouldReceive('getDefaultConnection')->once()->andReturn(null);
        DB::shouldReceive('connection')->twice()->andReturnSelf();
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

    protected function getRetrieveGlobalExportModeState(string $methodName, string $entity, bool $testCaseGlobalExportMode): bool
    {
        $testCaseMock = $this
            ->getMockBuilder(TestCase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $testCaseMock->setGlobalExportMode($testCaseGlobalExportMode);

        $reflectionMethod = new ReflectionMethod($testCaseMock, $methodName);
        $instance = $reflectionMethod->invoke($testCaseMock, $entity);

        $reflectionClass = new ReflectionClass($instance);
        $globalExportMode = $reflectionClass->getProperty('globalExportMode');

        return $globalExportMode->getValue($instance);
    }
}
