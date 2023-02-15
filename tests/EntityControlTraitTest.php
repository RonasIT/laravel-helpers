<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use ReflectionMethod;
use RonasIT\Support\Tests\Support\Mock\TestModel;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;

class EntityControlTraitTest extends HelpersTestCase
{
    use MockTrait;

    protected TestRepository $testRepositoryClass;
    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionMethod $getQueryMethod;

    public function setUp(): void
    {
        parent::setUp();

        $this->testRepositoryClass = new TestRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible('pubic');

        $this->getQueryMethod = new ReflectionMethod($this->testRepositoryClass, 'getQuery');
        $this->getQueryMethod->setAccessible('public');
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

    public function testExists()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null) as `exists`', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);
        $mock->expects($this->once())->method('postQueryHook');

        $mock->exists(['id' => 1]);
    }

    public function testExistsBy()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select exists(select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null) as `exists`', [2])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);
        $mock->expects($this->once())->method('postQueryHook');

        $mock->existsBy('id', 2);
    }

    public function testSelectToFetchAll(): void
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is null')
            ->shouldFetchAllReturns([[
                'id' => 1,
                'name' => 'John',
                'email' => 'john@example.com',
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]]);

        $this->assertEquals([[
            'id' => 1,
            'name' => 'John',
            'email' => 'john@example.com',
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]], TestModel::all()->toArray());
    }

    public function testUpdateMany()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` = ? and `test_models`.`deleted_at` is null', ['test_name', Carbon::now(), 1], 1);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->updateMany(1, ['name' => 'test_name']);
    }

//    public function testUpdate()
//    {
//        $pdo = DBMock::mockPdo();
//        $pdo
//            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
//            ->shouldFetchAllReturns([[
//                'id' => 1,
//                'name' => 'John',
//                'email' => 'john@example.com',
//                'created_at' => '2020-01-01 00:00:00',
//                'updated_at' => '2020-01-01 00:00:00',
//            ]]);
//
//        $pdo->shouldUpdateOne('update `test_models` set `test_models`.`updated_at` = ? where `id` = ? and `test_models`.`deleted_at` is null', [Carbon::now(), 1]);
//
//        $pdo
//            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
//            ->shouldFetchAllReturns([[
//                'id' => 1,
//                'name' => 'John',
//                'email' => 'john@example.com',
//                'created_at' => '2020-01-01 00:00:00',
//                'updated_at' => '2020-01-01 00:00:00',
//            ]]);
//
//        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);
//
//        $mock->expects($this->once())->method('postQueryHook');
//
//        $mock->update(1, []);
//    }

    public function testUpdateOrCreateEntityExists()
    {
        $pdo = DBMock::mockPdo();
        $pdo->

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties', 'postQueryHook']);

        $mock->expects($this->exactly(2))->method('resetSettableProperties');
        $mock->expects($this->exactly(2))->method('postQueryHook');

        $mock->updateOrCreate(1, []);
    }

    public function testCount()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `id` = ? and `test_models`.`deleted_at` is null', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->count(['id' => 1]);
    }

    public function testGet()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->get(['id' => 1]);
    }

    public function testFirst()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->first(1);
    }

    public function testfindBy()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->findBy('id', 1);
    }

    public function testFind()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->find(1);
    }

//    public function testFirstOrCreate()
//    {
//        TestModel::saving(fn () => false);
//
//        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties', 'postQueryHook']);
//
//        $mock->expects($this->exactly(2))->method('resetSettableProperties');
//        $mock->expects($this->exactly(2))->method('postQueryHook');
//
//        $mock->firstOrCreate(['id' => 1], []);
//    }

//    public function testFirstOrCreateEntityExists()
//    {
//        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties', 'first', 'postQueryHook']);
//
//        $mock->expects($this->exactly(2))->method('resetSettableProperties');
//        $mock->expects($this->once())->method('first')->willReturn(new TestModel());
//        $mock->expects($this->once())->method('postQueryHook');
//
//        $mock->firstOrCreate(['id' => 1], []);
//    }

//    public function testFirstOrCreateEntityDoesntExist()
//    {
//        $pdo = DBMock::mockPdo();
//        $pdo
//            ->shouldPrepareForSelect('select * from `test_models` where `id` = ? and `test_models`.`deleted_at` is null limit 1', [1])
//            ->shouldExecute()
//            ->shouldFetchAllReturns(['id' => 4]);
//
//        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties', 'postQueryHook']);
//
//        $mock->expects($this->exactly(2))->method('resetSettableProperties');
//        $mock->expects($this->once())->method('postQueryHook');
//
//        $mock->firstOrCreate(['id' => 1], []);
//    }

    public function testDelete()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateOne('update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `id` = ? and `test_models`.`deleted_at` is null', [Carbon::now(), Carbon::now(), 1]);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->delete(1);
    }

    public function testRestore()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateOne('update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `id` = ? and `test_models`.`deleted_at` is not null', [null, Carbon::now(), 1]);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->restore(1);
    }

    public function testDeleteByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows('update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `id` in (?, ?, ?) and `test_models`.`deleted_at` is null', [Carbon::now(), Carbon::now(), 1, 2, 3], 3);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->deleteByList([1, 2, 3]);
    }

    public function testRestoreByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo->shouldUpdateForRows('update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)', [null, Carbon::now(), 1, 2, 3], 3);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->restoreByList([1, 2, 3]);
    }

    public function testGetByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `id` in (?, ?, ?) and `test_models`.`deleted_at` is null', [1, 2, 3])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->getByList([1, 2, 3]);
    }

    public function testCountByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `id` in (?, ?, ?) and `test_models`.`deleted_at` is null', [1, 2, 3])
            ->whenFetchAllCalled();

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->countByList([1, 2, 3]);
    }

    public function testUpdateByList()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldUpdateForRows('update `test_models` set `name` = ?, `test_models`.`updated_at` = ? where `id` in (?, ?, ?) and `test_models`.`deleted_at` is null', ['test_name', Carbon::now(), 1, 2, 3], 3);

        $mock = $this->mockClass(TestRepository::class, ['postQueryHook']);

        $mock->expects($this->once())->method('postQueryHook');

        $mock->updateByList([1, 2, 3], ['name' => 'test_name']);
    }
}