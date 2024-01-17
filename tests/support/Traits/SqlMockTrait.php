<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Carbon;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy;

trait SqlMockTrait
{
    protected function mockAll(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null',
            [],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockGet(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockFirst(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockFirstBy(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockFind(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockFirstOrCreateEntityExists(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockGetByList(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [1, 2, 3],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockChunk(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 10 offset 0',
            [],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockCreate(array $selectResult): void
    {
        $pdo = DBMock::mockPdo();

        $this->mockInsert(
            'insert into `test_models` (`name`, `updated_at`, `created_at`) values (?, ?, ?)',
            [
                'test_name',
                Carbon::now(),
                Carbon::now()
            ],
            1,
            $pdo
        );

        $this->mockSelect(
            'select * from `test_models` where `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockUpdate(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            $selectResult,
            $pdo
        );

        $this->mockUpdateSqlQuery(
            'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?',
            [
                'test_name',
                Carbon::now(),
                1
            ],
            null,
            $pdo
        );

        $this->mockSelect(
            'select * from `test_models` where `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockUpdateOrCreateEntityExists(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select exists(select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`',
            [1],
            [
                ['exists' => true]
            ]
        );

        $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            $selectResult,
            $pdo
        );

        $this->mockUpdateSqlQuery(
            'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ?',
            [
                'test_name',
                Carbon::now(),
                1
            ],
            null,
            $pdo
        );

        $this->mockSelect(
            'select * from `test_models` where `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockUpdateOrCreateEntityDoesntExist(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select exists(select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`',
            [1],
            [
                ['exists' => false]
            ]
        );

        $this->mockInsert(
            'insert into `test_models` (`name`, `id`, `updated_at`, `created_at`) values (?, ?, ?, ?)',
            [
                'test_name',
                1,
                Carbon::now(),
                Carbon::now()
            ],
            1,
            $pdo
        );

        $this->mockSelect(
            'select * from `test_models` where `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockFirstOrCreateEntityDoesntExists(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1',
            [1]
        );

        $this->mockInsert(
            'insert into `test_models` (`name`, `id`, `updated_at`, `created_at`) values (?, ?, ?, ?)',
            [
                'test_name',
                1,
                Carbon::now(),
                Carbon::now()
            ],
            1,
            $pdo
        );

        $this->mockSelect(
            'select * from `test_models` where `id` = ? limit 1',
            [1],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockGetSearchResult(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null',
            [],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 15 offset 0',
            [],
            $selectResult,
            $pdo
        );

        $this->mockSelect(
            'select * from `relation_models` where `relation_models`.`test_model_id` in (1)',
            [],
            [],
            $pdo
        );
    }

    protected function mockGetSearchResultWithTrashed(): void
    {
        $pdo = $this->mockSelect(
            'select count(*) as aggregate from `test_models`',
            [],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            'select * from `test_models` order by `id` asc limit 15 offset 0',
            [],
            [],
            $pdo
        );
    }

    protected function mockGetSearchResultWithQuery(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            "select count(*) as aggregate from `test_models` "
            . "where ((`query_field` like '%search_string%') or (`another_query_field` like '%search_string%')) "
            . "and `test_models`.`deleted_at` is null",
            [],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            "select * from `test_models` where ((`query_field` like '%search_string%') "
            . "or (`another_query_field` like '%search_string%')) and `test_models`.`deleted_at` is null "
            . "order by `id` asc limit 15 offset 0",
            [],
            $selectResult,
            $pdo
        );
    }

    protected function mockGetSearchResultWithCustomQuery(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            'select count(*) as aggregate from "test_models" '
            . 'where (("query_field"::text ilike \'%\' || unaccent(\'search_string\') || \'%\') '
            . 'or ("another_query_field"::text ilike \'%\' || unaccent(\'search_string\') || \'%\')) '
            . 'and "test_models"."deleted_at" is null',
            [],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            'select * from "test_models" '
            . 'where (("query_field"::text ilike \'%\' || unaccent(\'search_string\') || \'%\') '
            . 'or ("another_query_field"::text ilike \'%\' || unaccent(\'search_string\') || \'%\')) '
            . 'and "test_models"."deleted_at" is null order by "id" asc limit 15 offset 0',
            [],
            $selectResult,
            $pdo
        );
    }

    protected function mockGetSearchResultWithRelations(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            "select count(*) as aggregate from `test_models` "
            . "where ((`query_field` like '%search_string%') or exists (select * from `relation_models` "
            . "where `test_models`.`id` = `relation_models`.`test_model_id` "
            . "and (`another_query_field` like '%search_string%'))) and exists (select * from `relation_models` "
            . "where `test_models`.`id` = `relation_models`.`test_model_id` and `name` in (?)) "
            . "and `test_models`.`deleted_at` is null",
            ['some_value'],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            "select `test_models`.*, (select `id` from `relation_models` "
            . "where `test_models`.`id` = `relation_models`.`test_model_id` order by `id` asc limit 1) "
            . "as `relation_id` from `test_models` where ((`query_field` like '%search_string%') "
            . "or exists (select * from `relation_models` "
            . "where `test_models`.`id` = `relation_models`.`test_model_id` "
            . "and (`another_query_field` like '%search_string%'))) and "
            . "exists (select * from `relation_models` where `test_models`.`id` = `relation_models`.`test_model_id` "
            . "and `name` in (?)) and `test_models`.`deleted_at` is null "
            . "order by `relation_id` asc, `id` asc limit 15 offset 0",
            ['some_value'],
            $selectResult,
            $pdo
        );
    }

    protected function mockGetSearchResultWithFilters(array $selectResult): void
    {
        $pdo = $this->mockSelect(
            "select count(*) as aggregate from `test_models` where `user_id` in (?, ?) and `user_id` "
            . "not in (?, ?) and `name` in (?) and `date` >= ? and `date` <= ? "
            . "and `created_at` >= ? and `created_at` <= ? and `updated_at` > ? "
            . "and `updated_at` < ? and `test_models`.`deleted_at` is null",
            [
                1,
                2,
                3,
                4,
                'text_name',
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
            ],
            [
                ['aggregate' => 1]
            ]
        );

        $this->mockSelect(
            "select * from `test_models` where `user_id` in (?, ?) and `user_id` not in (?, ?) "
            . "and `name` in (?) and `date` >= ? and `date` <= ? and `created_at` >= ? and `created_at` <= ? "
            . "and `updated_at` > ? and `updated_at` < ? and `test_models`.`deleted_at` is null "
            . "order by `id` asc limit 15 offset 0",
            [
                1,
                2,
                3,
                4,
                'text_name',
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
                Carbon::now(),
            ],
            $selectResult,
            $pdo
        );
    }

    protected function mockExistsUsersExceptAuthorized(bool $isExist, string $table = 'users'): void
    {
        $this->mockSelect(
            "select exists(select * from `{$table}` where `id` <> ? and `email` in (?)) as `exists`",
            [1, 'mail@mail.com'],
            [
                ['exists' => $isExist]
            ]
        );
    }

    protected function mockExistsUsersExceptAuthorizedByArray(bool $isExist, string $table = 'users', string $keyField = 'id'): void
    {
        $this->mockSelect(
            "select exists(select * from `{$table}` where `{$keyField}` <> ? and `email` in (?, ?)) as `exists`",
            [
                1,
                'mail@mail.com',
                'mail@mail.net'
            ],
            [
                ['exists' => $isExist]
            ]
        );
    }

    protected function mockUpdateSqlQuery(string $sql, array $bindings = [], ?int $rowCount = null, SingleConnectionProxy $pdo = null): SingleConnectionProxy
    {
        if (empty($pdo)) {
            $pdo = DBMock::mockPdo();
        }

        if (!empty($rowCount)) {
            $pdo->shouldUpdateForRows($sql, $bindings, $rowCount);
        } else {
            $pdo->shouldUpdateOne($sql, $bindings);
        }

        return $pdo;
    }

    protected function mockDelete(string $sql, array $bindings = [], ?int $rowCount = null, SingleConnectionProxy $pdo = null): SingleConnectionProxy
    {
        if (empty($pdo)) {
            $pdo = DBMock::mockPdo();
        }

        if (!empty($rowCount)) {
            $pdo->shouldDeleteForRows($sql, $bindings, $rowCount);
        } else {
            $pdo->shouldDeleteOne($sql, $bindings);
        }

        return $pdo;
    }

    protected function mockInsert(string $sql, array $data, int $lastInsertId, SingleConnectionProxy $pdo = null): SingleConnectionProxy
    {
        if (empty($pdo)) {
            $pdo = DBMock::mockPdo();
        }

        $pdo->shouldInsert($sql, $data);
        $pdo->expects('lastInsertId')->andReturn($lastInsertId);

        return $pdo;
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