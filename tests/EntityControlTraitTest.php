<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use ReflectionProperty;
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
        $this->onlyTrashedProperty->setAccessible('pubic');

        $this->withTrashedProperty = new ReflectionProperty(TestRepository::class, 'withTrashed');
        $this->withTrashedProperty->setAccessible('pubic');

        $this->forceModeProperty = new ReflectionProperty(TestRepository::class, 'forceMode');
        $this->forceModeProperty->setAccessible('pubic');

        $this->attachedRelationsProperty = new ReflectionProperty(TestRepository::class, 'attachedRelations');
        $this->attachedRelationsProperty->setAccessible('pubic');

        $this->attachedRelationsCountProperty = new ReflectionProperty(TestRepository::class, 'attachedRelationsCount');
        $this->attachedRelationsCountProperty->setAccessible('pubic');

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

    public function withCount()
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
            ->withCount('relation')
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
            . 'from `test_models` where `test_models`.`deleted_at` is not null and `id` = ?) as `exists`',
            [2]
        );

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->existsBy('id', 2);

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testCreate()
    {
        $this->mockCreate($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->create(['name' => 'test_name']);

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
        $this->mockUpdate($this->selectResult);

        $this->testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->update(1, ['name' => 'test_name']);

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

    public function testDelete()
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

    public function testDeleteByList()
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
}