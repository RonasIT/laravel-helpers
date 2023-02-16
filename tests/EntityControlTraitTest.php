<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use ReflectionMethod;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class EntityControlTraitTest extends HelpersTestCase
{
    use MockTrait, SqlMockTrait;

    protected TestRepository $testRepositoryClass;
    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionMethod $getQueryMethod;

    protected array $selectResult;

    public function setUp(): void
    {
        parent::setUp();

        $this->testRepositoryClass = new TestRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible('pubic');

        $this->getQueryMethod = new ReflectionMethod($this->testRepositoryClass, 'getQuery');
        $this->getQueryMethod->setAccessible('public');

        $this->selectResult = $this->getJsonFixture('select_query_result.json');

        Carbon::setTestNow('2020-01-01 00:00:00');
    }

    public function testOnlyTrashed()
    {
        $this->testRepositoryClass->onlyTrashed();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
    }

    public function testGetQuery()
    {
        $sql = $this->getQueryMethod->invoke($this->testRepositoryClass)->toSql();

        $this->assertEqualsFixture('get_query_sql.json', $sql);
    }

    public function testAll()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null')
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->all();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testExists()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->exists(['id' => 1]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testExistsBy()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`', [2])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->existsBy('id', 2);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testCreate()
    {
        $this->mockCreate($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->create(['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdateMany()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows(
            'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` = ?',
            ['test_name', Carbon::now(), 1],
            1
        );

        $this->testRepositoryClass->onlyTrashed()->updateMany(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdate()
    {
        $this->mockUpdate($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->update(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdateDoesntExist()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->shouldFetchAllReturns([]);

        $this->testRepositoryClass->onlyTrashed()->update(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdateOrCreateEntityExists()
    {
        $this->mockUpdateOrCreateEntityExists($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->updateOrCreate(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdateOrCreateEntityDoesntExist()
    {
        $this->mockUpdateOrCreateEntityDoesntExist($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->updateOrCreate(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testCount()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->count(['id' => 1]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testGet()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->get(['id' => 1]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testFirst()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->first(1);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testFindBy()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->findBy('id', 1);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testFind()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->find(1);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testFirstOrCreateEntityExists()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1', [1])
            ->shouldFetchAllReturns($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->firstOrCreate(1, ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testFirstOrCreateEntityDoesntExists()
    {
        $this->mockFirstOrCreateEntityDoesntExists($this->selectResult);

        $this->testRepositoryClass->onlyTrashed()->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testDelete()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateOne(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` = ?',
            [Carbon::now(), Carbon::now(), 1]
        );

        $this->testRepositoryClass->onlyTrashed()->delete(1);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testRestore()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateOne(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` = ? and `test_models`.`deleted_at` is not null',
            [null, Carbon::now(), 1]
        );

        $this->testRepositoryClass->onlyTrashed()->restore(1);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testChunk()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 10 offset 0')
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->chunk(10, function () {});

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testDeleteByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [Carbon::now(), Carbon::now(), 1, 2, 3],
            3
        );

        $this->testRepositoryClass->onlyTrashed()->deleteByList([1, 2, 3]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testRestoreByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [null, Carbon::now(), 1, 2, 3],
            3
        );

        $this->testRepositoryClass->onlyTrashed()->restoreByList([1, 2, 3]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testGetByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)', [1, 2, 3])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->getByList([1, 2, 3]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testCountByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)', [1, 2, 3])
            ->whenFetchAllCalled();

        $this->testRepositoryClass->onlyTrashed()->countByList([1, 2, 3]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testUpdateByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldUpdateForRows(
                'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
                ['test_name', Carbon::now(), 1, 2, 3],
                3
            );

        $this->testRepositoryClass->onlyTrashed()->updateByList([1, 2, 3], ['name' => 'test_name']);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }
}