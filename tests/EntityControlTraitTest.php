<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use RonasIT\Support\Exceptions\InvalidModelException;
use RonasIT\Support\Tests\Support\Mock\TestModel;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use ReflectionProperty;
use RonasIT\Support\Tests\Support\Mock\TestRepositoryNoPrimaryKey;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class EntityControlTraitTest extends HelpersTestCase
{
    use SqlMockTrait;

    protected TestRepository $testRepositoryClass;

    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $attachedRelationsProperty;
    protected ReflectionProperty $attachedRelationsCountProperty;

    protected array $selectResult;

    public function setUp(): void
    {
        parent::setUp();

        $this->testRepositoryClass = new TestRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible(true);

        $this->withTrashedProperty = new ReflectionProperty(TestRepository::class, 'withTrashed');
        $this->withTrashedProperty->setAccessible(true);

        $this->forceModeProperty = new ReflectionProperty(TestRepository::class, 'forceMode');
        $this->forceModeProperty->setAccessible(true);

        $this->attachedRelationsProperty = new ReflectionProperty(TestRepository::class, 'attachedRelations');
        $this->attachedRelationsProperty->setAccessible(true);

        $this->attachedRelationsCountProperty = new ReflectionProperty(TestRepository::class, 'attachedRelationsCount');
        $this->attachedRelationsCountProperty->setAccessible(true);

        $this->selectResult = $this->getJsonFixture('select_query_result.json');

        Carbon::setTestNow('2020-01-01 00:00:00');
    }

    public function testOnlyTrashed()
    {
        $this->testRepositoryClass->onlyTrashed();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertTrue($onlyTrashed);
    }

    public function testWithTrashed()
    {
        $this->testRepositoryClass->withTrashed();

        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertTrue($withTrashed);
    }

    public function testForce()
    {
        $this->testRepositoryClass->force();

        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);

        $this->assertTrue($forceMode);
    }

    public function testWith()
    {
        $this->testRepositoryClass->with('relation');

        $attachedRelations = $this->attachedRelationsProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(['relation'], $attachedRelations);
    }

    public function testWithCount()
    {
        $this->testRepositoryClass->withCount('relation');

        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(['relation'], $attachedRelationsCount);
    }

    public function testAll()
    {
        $this->mockAll($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount(['relation', 'relation.child_relation'])
            ->force()
            ->all();

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testAllEmptyResult()
    {
        $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->force()
            ->all();

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testExists()
    {
        $this->mockSelectById(
            'select exists(select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`',
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->exists(['id' => 1]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testExistsBy()
    {
        $this->mockSelectExists(
            'select exists(select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->existsBy('id', 1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testCreate()
    {
        $this->mockCreate($this->selectResult, null);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->create([
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testCreateOnlyFillable()
    {
        $this->mockCreate($this->selectResult, Carbon::now());

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->create([
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateMany()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? '
            . 'where `test_models`.`deleted_at` is not null and `id` = ?',
            ['test_name', Carbon::now(), 1],
            1
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateMany(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdate()
    {
        $this->mockUpdate($this->selectResult, null);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->update(1, [
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateOnlyFillable()
    {
        $this->mockUpdate($this->selectResult, Carbon::now());

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->update(1, [
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateDoesntExist()
    {
        $this->mockSelectById(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->update(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateOrCreateEntityExists()
    {
        $this->mockUpdateOrCreateEntityExists($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateOrCreate(1, ['name' => 'test_name']);

       $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateOrCreateEntityDoesntExist()
    {
        $this->mockUpdateOrCreateEntityDoesntExist($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateOrCreate(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testCount()
    {
        $this->mockSelectById(
            'select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->count(['id' => 1]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testGet()
    {
        $this->mockGet($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->get(['id' => 1]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testGetEmptyResult()
    {
        $this->mockSelectById(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->get(['id' => 1]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFirst()
    {
        $this->mockFirst($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->first(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testLast()
    {
        $this->mockLast($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->last(['id' => 1]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFirstEmptyResult()
    {
        $this->mockSelectById(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->first(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFindBy()
    {
        $this->mockFirstBy($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->findBy('id', 1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFindByEmptyResult()
    {
        $this->mockSelectById(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->findBy('id', 1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFind()
    {
        $this->mockFind($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->find(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFindEmptyResult()
    {
        $this->mockSelectById(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ? limit 1'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->find(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFirstOrCreateEntityExists()
    {
        $this->mockFirstOrCreateEntityExists($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testFirstOrCreateEntityDoesntExists()
    {
        $this->mockFirstOrCreateEntityDoesntExists($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testForceDelete()
    {
        $this->mockDelete('delete from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?', [1]);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->delete(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testDelete()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? '
            . 'where `id` = ?',
            [Carbon::now(), Carbon::now(), 1]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->with('relation')
            ->withCount('relation')
            ->delete(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testRestore()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? '
            . 'where `test_models`.`deleted_at` is not null and `id` = ? and `test_models`.`deleted_at` is not null',
            [null, Carbon::now(), 1]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->restore(1);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testChunk()
    {
        $this->mockChunk($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->chunk(10, function () {});

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testChunkEmptyResult()
    {
        $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null order by `id` asc limit 10 offset 0'
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->chunk(10, function () {});

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testForceDeleteByList()
    {
        $this->mockDelete(
            'delete from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [1, 2, 3],
            3
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->deleteByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testDeleteByList()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? where `id` in (?, ?, ?)',
            [Carbon::now(), Carbon::now(), 1, 2, 3]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->with('relation')
            ->withCount('relation')
            ->deleteByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testRestoreByList()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `deleted_at` = ?, `test_models`.`updated_at` = ? '
            . 'where `test_models`.`deleted_at` is not null '
            . 'and `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [null, Carbon::now(), 1, 2, 3],
            3
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->restoreByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testGetByList()
    {
        $this->mockGetByList($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->getByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testGetByListEmptyResult()
    {
        $this->mockSelect(
            'select `test_models`.*, (select count(*) from `relation_models` '
            . 'where `test_models`.`id` = `relation_models`.`test_model_id`) as `relation_count` '
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [],
            [1, 2, 3]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->getByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testCountByList()
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from `test_models` '
            . 'where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            [1, 2, 3]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->countByList([1, 2, 3]);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testUpdateByList()
    {
        $this->mockUpdateSqlQuery(
            'update `test_models` set `name` = ?, `test_models`.`updated_at` = ? '
            . 'where `test_models`.`deleted_at` is not null and `id` in (?, ?, ?)',
            ['test_name', Carbon::now(), 1, 2, 3],
            3
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateByList([1, 2, 3], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testTruncate()
    {
        $this->mockUpdateSqlQuery('truncate table `test_models`');

        $this->testRepositoryClass->truncate();
    }

    public function testModelWithoutPrimaryKey()
    {
        $this->expectException(InvalidModelException::class);
        $this->expectExceptionMessage(
            'Model RonasIT\Support\Tests\Support\Mock\TestModelNoPrimaryKey must have primary key.'
        );

        new TestRepositoryNoPrimaryKey();
    }

    public function testGetEntityName()
    {
        $name = $this->testRepositoryClass->getModelName();

        $this->assertEquals('TestModel', $name);
    }
}