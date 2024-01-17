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
        $connection = $this->mockClass(Connection::class, [], true);

        DB::swap($connection);

        $builderMock
            ->method('orderBy')
            ->willReturn($builderMock);

        $builderMock
            ->method('get')
            ->willReturn($responseMock);

        $connection
            ->method('table')
            ->willReturn($builderMock);
    }

    protected function mockGettingDatasetForChanges(Collection $responseMock, Collection $initialState): void
    {
        $builderMock = $this->mockClass(Builder::class, ['orderBy', 'get'], true);
        $connection = $this->mockClass(Connection::class, [], true);

        DB::swap($connection);

        $builderMock
            ->method('orderBy')
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($initialState, $responseMock);

        $connection
            ->method('table')
            ->willReturn($builderMock);
    }
}
