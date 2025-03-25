<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use Mpyw\LaravelDatabaseMock\Proxies\SingleConnectionProxy;

trait SqlMockTrait
{
    protected SingleConnectionProxy $pdo;

    protected function mockAll(array $selectResult): void
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null',
            $selectResult
        );

        $this->mockSelect(
            'select "relation_models".*, (select count(*) from "child_relation_models" '
            . 'where "relation_models"."id" = "child_relation_models"."relation_model_id") as "child_relation_count" '
            . 'from "relation_models" where "relation_models"."test_model_id" in (1)',
        );
    }

    protected function mockGet(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockFirst(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockFirstBy(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockLast(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? '
            . 'order by "created_at" desc limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockFind(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockFirstOrCreateEntityExists(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockGetByList(array $selectResult): void
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            $selectResult,
            [1, 2, 3],
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockChunk(array $selectResult): void
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null order by "id" asc limit 10 offset 0',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockCreate(array $selectResult, $notFillableValue): void
    {
        $this->mockInsert(
            'insert into "test_models" ("name", "updated_at", "created_at") values (?, ?, ?)',
            ['test_name', $notFillableValue, Carbon::now()]
        );

        $this->mockSelectById(
            'select * from "test_models" where "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockInsertData(): void
    {
        $query = 'insert into "test_models" ("created_at", "name", "updated_at") values (?, ?, ?), (?, ?, ?), (?, ?, ?)';

        $values = [
            $this->mockedNow, 'test_name_1', $this->mockedNow,
            $this->mockedNow, 'test_name_2', $this->mockedNow,
            $this->mockedNow, 'test_name_3', $this->mockedNow,
        ];

        $this->getPdo()->shouldInsert($query, $values);
    }

    protected function mockInsertDataWithoutTimestamps(): void
    {
        $query = 'insert into "test_models" ("created_at", "name") values (?, ?), (?, ?), (?, ?)';

        $values = [
            '1999-01-01', 'test_name_1',
            '1999-01-01', 'test_name_2',
            '1999-01-01', 'test_name_3',
        ];

        $this->getPdo()->shouldInsert($query, $values);
    }

    protected function mockInsertDataWithDifferentTimestampNames(): void
    {
        $query = 'insert into "test_models" ("creation_date", "name", "updated_date") values (?, ?, ?), (?, ?, ?), (?, ?, ?)';

        $values = [
            '1999-01-01', 'test_name_1', $this->mockedNow,
            '1999-01-01', 'test_name_2', $this->mockedNow,
            '1999-01-01', 'test_name_3', $this->mockedNow,
        ];

        $this->getPdo()->shouldInsert($query, $values);
    }

    protected function mockUpdate(array $selectResult, $notFillableValue): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)',
            $selectResult
        );

        $this->mockUpdateSqlQuery(
            'update "test_models" set "name" = ?, "updated_at" = ? where "id" = ?',
            ['test_name', $notFillableValue, 1]
        );

        $this->mockSelectById(
            'select * from "test_models" where "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockUpdateOrCreateEntityExists(array $selectResult): void
    {
        $this->mockSelectExists(
            'select exists(select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?) as "exists"'
        );

        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)',
            $selectResult
        );

        $this->mockUpdateSqlQuery(
            'update "test_models" set "name" = ?, "updated_at" = ? where "id" = ?',
            ['test_name', Carbon::now(), 1]
        );

        $this->mockSelectById(
            'select * from "test_models" where "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockUpdateOrCreateEntityDoesntExist(array $selectResult): void
    {
        $this->mockSelectExists(
            'select exists(select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?) as "exists"',
            false
        );

        $this->mockInsert(
            'insert into "test_models" ("name", "id", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['test_name', 1, Carbon::now(), Carbon::now()]
        );

        $this->mockSelectById(
            'select * from "test_models" where "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockFirstOrCreateEntityDoesntExists(array $selectResult): void
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1'
        );

        $this->mockInsert(
            'insert into "test_models" ("name", "id", "updated_at", "created_at") values (?, ?, ?, ?)',
            ['test_name', 1, Carbon::now(), Carbon::now()]
        );

        $this->mockSelectById(
            'select * from "test_models" where "id" = ? limit 1',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockGetSearchResult(array $selectResult): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" where "test_models"."deleted_at" is not null'
        );

        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null order by "id" asc limit 15 offset 0',
            $selectResult
        );

        $this->mockSelect(
            'select * from "relation_models" where "relation_models"."test_model_id" in (1)'
        );
    }

    protected function mockGetSearchResultWithTrashed(): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models"'
        );

        $this->mockSelect(
            'select * from "test_models" order by "id" asc limit 15 offset 0'
        );
    }

    protected function mockGetSearchResultWithQuery(array $selectResult): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" '
            . "where ((\"query_field\" like '%search_\'string%') or (\"another_query_field\" like '%search_\'string%')) "
            . 'and "test_models"."deleted_at" is null'
        );

        $this->mockSelect(
            "select * from \"test_models\" where ((\"query_field\" like '%search_\'string%') "
            . "or (\"another_query_field\" like '%search_\'string%')) and \"test_models\".\"deleted_at\" is null "
            . 'order by "id" asc limit 15 offset 0',
            $selectResult
        );
    }

    protected function mockGetSearchResultWithCustomQuery(array $selectResult): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" '
            . 'where (("query_field"::text ilike \'%\' || unaccent(\'search_\'\'string\') || \'%\') '
            . 'or ("another_query_field"::text ilike \'%\' || unaccent(\'search_\'\'string\') || \'%\')) '
            . 'and "test_models"."deleted_at" is null'
        );

        $this->mockSelect(
            'select * from "test_models" '
            . 'where (("query_field"::text ilike \'%\' || unaccent(\'search_\'\'string\') || \'%\') '
            . 'or ("another_query_field"::text ilike \'%\' || unaccent(\'search_\'\'string\') || \'%\')) '
            . 'and "test_models"."deleted_at" is null order by "id" asc limit 15 offset 0',
            $selectResult
        );
    }

    protected function mockGetSearchResultWithRelations(array $selectResult): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" '
            . 'where (("query_field" like \'%search_string%\') or exists (select * from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id" '
            . 'and ("another_query_field" like \'%search_string%\'))) and exists (select * from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id" and "name" = ?) '
            . 'and "test_models"."deleted_at" is null',
            ['some_value']
        );

        $this->mockSelect(
            'select "test_models".*, (select "id" from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id" order by "id" asc limit 1) '
            . 'as "relation_id" from "test_models" where (("query_field" like \'%search_string%\') '
            . 'or exists (select * from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id" '
            . 'and ("another_query_field" like \'%search_string%\'))) and '
            . 'exists (select * from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" '
            . 'and "name" = ?) and "test_models"."deleted_at" is null '
            . 'order by "relation_id" asc, "id" asc limit 15 offset 0',
            $selectResult,
            ['some_value'],
        );
    }

    protected function mockGetSearchResultWithFilters(array $selectResult): void
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" where "user_id" in (?, ?) and "user_id" '
            . 'not in (?, ?) and "name" = ? and "date" >= ? and "date" <= ? '
            . 'and "created_at" >= ? and "created_at" <= ? and "updated_at" > ? '
            . 'and "updated_at" < ? and "test_models"."deleted_at" is null',
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
            ]
        );

        $this->mockSelect(
            'select * from "test_models" where "user_id" in (?, ?) and "user_id" not in (?, ?) '
            . 'and "name" = ? and "date" >= ? and "date" <= ? and "created_at" >= ? and "created_at" <= ? '
            . 'and "updated_at" > ? and "updated_at" < ? and "test_models"."deleted_at" is null '
            . 'order by "id" asc limit 15 offset 0',
            $selectResult,
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
            ]
        );
    }

    protected function mockExistsUsersExceptAuthorized(): void
    {
        $this->mockSelectExists(
            'select exists(select * from "users" where "id" <> ? and "email" in (?)) as "exists"',
            false,
            [1, 'mail@mail.com']
        );
    }

    protected function mockExistsUsersExceptAuthorizedByArray(
        bool $isExist,
        string $table = 'users',
        string $keyField = 'id'
    ): void {
        $this->mockSelectExists(
            "select exists(select * from \"{$table}\" where \"{$keyField}\" <> ? and \"email\" in (?, ?)) as \"exists\"",
            $isExist,
            [1, 'mail@mail.com', 'mail@mail.net']
        );
    }

    protected function mockListExists(
        array $result,
        string $table = 'clients',
        string $keyField = 'user_id',
    ): void {
        $this->mockSelect(
            query: "select count(distinct \"{$keyField}\") as aggregate from \"{$table}\" where \"{$keyField}\" in (?, ?, ?)",
            result: [['aggregate' => count($result)]],
            bindings: [1, 2, 3]
        );
    }

    protected function mockUpdateSqlQuery(string $sql, array $bindings = [], ?int $rowCount = null): void
    {
        if (!empty($rowCount)) {
            $this->getPdo()->shouldUpdateForRows($sql, $bindings, $rowCount);
        } else {
            $this->getPdo()->shouldUpdateOne($sql, $bindings);
        }
    }

    protected function mockDelete(string $sql, array $bindings = [], ?int $rowCount = null): void
    {
        if (!empty($rowCount)) {
            $this->getPdo()->shouldDeleteForRows($sql, $bindings, $rowCount);
        } else {
            $this->getPdo()->shouldDeleteOne($sql, $bindings);
        }
    }

    protected function mockInsert(string $sql, array $data, int $lastInsertId = 1): void
    {
        $this->getPdo()->shouldInsert($sql, $data);
        $this->getPdo()->expects('lastInsertId')->andReturn($lastInsertId);
    }

    protected function mockSelect(string $query, array $result = [], array $bindings = []): void
    {
        $select = $this->getPdo()->shouldSelect($query, $bindings);

        if (!empty($result)) {
            $select->shouldFetchAllReturns($result);
        } else {
            $select->whenFetchAllCalled();
        }
    }

    protected function mockSelectWithAggregate(string $query, array $bindings = [], ?int $result = 1): void
    {
        $this->mockSelect($query, [['aggregate' => $result]], $bindings);
    }

    protected function mockSelectById(string $query, array $result = []): void
    {
        $this->mockSelect($query, $result, [1]);
    }

    protected function mockSelectExists(string $query, bool $isExist = true, array $bindings = [1]): void
    {
        $this->mockSelect($query, [['exists' => $isExist]], $bindings);
    }

    protected function mockTruncate(string $table): void
    {
        $this->mockDelete("delete from sqlite_sequence where name = ?", [$table]);

        $this->mockDelete("delete from \"{$table}\"");
    }

    protected function getPdo(): SingleConnectionProxy
    {
        $this->pdo ??= DBMock::mockPdo();

        return $this->pdo;
    }

    protected function mockStatementDBFacade(array $sql): void
    {
        DB::shouldReceive('statement')
            ->times(count($sql))
            ->withArgs(fn ($query) => in_array($query, $sql))
            ->andReturn(true);
    }
}
