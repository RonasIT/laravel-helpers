<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Carbon;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy;

trait SqlMockTrait
{
    protected function mockAll(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null', [], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockGet(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockFirst(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockFirstBy(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockFind(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockFirstOrCreateEntityExists(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockGetByList(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)', [1, 2, 3], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockChunk(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 10 offset 0', [], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockCreate(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();

        $pdo->shouldInsert('insert into `test_models` (`name`, `updated_at`, `created_at`) values (?, ?, ?)', ['test_name', Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $this->mockSelect('select * from `test_models` where `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockUpdate(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], $selectResult, $pdo);

        $pdo->shouldUpdateOne('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?', ['test_name', Carbon::now(), 1]);

        $this->mockSelect('select * from `test_models` where `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockUpdateOrCreateEntityExists(array $selectResult): void
    {
        $pdo = $this->mockSelect('select exists(select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [1], [['exists' => true]]);

        $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], $selectResult, $pdo);

        $pdo->shouldUpdateOne('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?', ['test_name', Carbon::now(), 1]);

        $this->mockSelect('select * from `test_models` where `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockUpdateOrCreateEntityDoesntExist(array $selectResult): void
    {
        $pdo = $this->mockSelect('select exists(select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [1], [['exists' => false]]);

        $pdo->shouldInsert('insert into `test_models` (`name`, `id`, `updated_at`, `created_at`) values (?, ?, ?, ?)', ['test_name', 1, Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $this->mockSelect('select * from `test_models` where `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockFirstOrCreateEntityDoesntExists(array $selectResult): void
    {
        $pdo = $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1]);

        $pdo->shouldInsert('insert into `test_models` (`name`, `id`, `updated_at`, `created_at`) values (?, ?, ?, ?)', ['test_name', 1, Carbon::now(), Carbon::now()]);
        $pdo->expects('lastInsertId')->andReturn(1);

        $this->mockSelect('select * from `test_models` where `id` = ? limit 1', [1], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockGetSearchResult(array $selectResult): void
    {
        $pdo = $this->mockSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null', [], [['aggregate' => 1]]);

        $this->mockSelect('select `test_models`.*, (select count(*) from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 15 offset 0', [], $selectResult, $pdo);

        $this->mockSelect('select * from `relation_models` where `relation_models`.`test_model_id` in (1)', [], [], $pdo);
    }

    protected function mockGetSearchResultWithTrashed(): void
    {
        $pdo = $this->mockSelect('select count(*) as aggregate from `test_models`', [], [['aggregate' => 1]]);

        $this->mockSelect('select * from `test_models` order by `id` asc limit 15 offset 0', [], [], $pdo);
    }

    protected function mockSelect(string $query, array $bindings = [], array $result = [], SingleConnectionProxy $pdo = null): SingleConnectionProxy
    {
        if (empty($pdo)) {
            $pdo = DBMock::mockPdo();
        }

        $select = $pdo->shouldSelect($query, $bindings);

        if (!empty($result)) {
            $select->shouldFetchAllReturns($result);
        } else {
            $select->whenFetchAllCalled();
        }

        return $pdo;
    }
}