<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ModelTestStateMockTrait
{
    use MockTestTrait;

    protected function mockGettingDataset(Collection $responseMock): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);

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
}
