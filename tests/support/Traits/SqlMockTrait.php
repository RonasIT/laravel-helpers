<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Carbon;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;

trait SqlMockTrait
{
    public function mockCreate(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldInsert('insert into `test_models` (`name`, `updated_at`, `created_at`) values (?, ?, ?)', ['test_name', Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);
    }

    public function mockUpdate(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);

        $pdo->shouldUpdateOne('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?', ['test_name', Carbon::now(), 1]);

        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);
    }

    public function mockUpdateOrCreateEntityExists(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [1])
            ->shouldFetchAllReturns([['exists' => true]]);

        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);

        $pdo->shouldUpdateOne('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?', ['test_name', Carbon::now(), 1]);

        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);
    }

    public function mockUpdateOrCreateEntityDoesntExist(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [1])
            ->shouldFetchAllReturns([['exists' => false]]);

        $pdo->shouldInsert('insert into `test_models` (`name`, `updated_at`, `created_at`) values (?, ?, ?)', ['test_name', Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);
    }

    public function mockFirstOrCreateEntityDoesntExists(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->shouldFetchAllReturns([]);

        $pdo->shouldInsert('insert into `test_models` (`name`, `updated_at`, `created_at`) values (?, ?, ?)', ['test_name', Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($selectResult);
    }

    public function mockGetSearchResult(): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is null')
            ->shouldFetchAllReturns([['aggregate' => 1]]);

        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is null order by `id` asc limit 15 offset 0')
            ->whenFetchAllCalled();
    }

    public function mockGetSearchResultWithOnlyTrash(): void
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null')
            ->shouldFetchAllReturns([['aggregate' => 1]]);

        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 15 offset 0')
            ->whenFetchAllCalled();
    }
}