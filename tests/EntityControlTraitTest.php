<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Carbon;
use ReflectionProperty;
use RonasIT\Support\Exceptions\InvalidModelException;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepositoryNoPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepositoryWithDifferentTimestampNames;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepositoryWithoutTimestamps;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class EntityControlTraitTest extends TestCase
{
    use SqlMockTrait;

    protected string $mockedNow = '2020-01-01 00:00:00';

    protected static array $selectResult;

    protected static TestRepository $testRepositoryClass;
    protected static TestRepositoryWithoutTimestamps $testRepositoryClassWithoutTimestamps;
    protected static TestRepositoryWithDifferentTimestampNames $testRepositoryWithDifferentTimestampNames;

    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $attachedRelationsProperty;
    protected ReflectionProperty $attachedRelationsCountProperty;

    public function setUp(): void
    {
        parent::setUp();

        self::$testRepositoryClass ??= new TestRepository();
        self::$testRepositoryClassWithoutTimestamps ??= new TestRepositoryWithoutTimestamps();
        self::$testRepositoryWithDifferentTimestampNames ??= new TestRepositoryWithDifferentTimestampNames();

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');

        $this->withTrashedProperty = new ReflectionProperty(TestRepository::class, 'withTrashed');

        $this->forceModeProperty = new ReflectionProperty(TestRepository::class, 'forceMode');

        $this->attachedRelationsProperty = new ReflectionProperty(TestRepository::class, 'attachedRelations');

        $this->attachedRelationsCountProperty = new ReflectionProperty(TestRepository::class, 'attachedRelationsCount');

        self::$selectResult ??= $this->getJsonFixture('select_query_result.json');

        Carbon::setTestNow($this->mockedNow);
    }

    public function testOnlyTrashed()
    {
        self::$testRepositoryClass->onlyTrashed();

        $onlyTrashed = $this->onlyTrashedProperty->getValue(self::$testRepositoryClass);

        $this->assertTrue($onlyTrashed);
    }

    public function testWithTrashed()
    {
        self::$testRepositoryClass->withTrashed();

        $withTrashed = $this->withTrashedProperty->getValue(self::$testRepositoryClass);

        $this->assertTrue($withTrashed);
    }

    public function testForce()
    {
        self::$testRepositoryClass->force();

        $forceMode = $this->forceModeProperty->getValue(self::$testRepositoryClass);

        $this->assertTrue($forceMode);
    }

    public function testWith()
    {
        self::$testRepositoryClass->with('relation');

        $attachedRelations = $this->attachedRelationsProperty->getValue(self::$testRepositoryClass);

        $this->assertEquals(['relation'], $attachedRelations);
    }

    public function testWithCount()
    {
        self::$testRepositoryClass->withCount('relation');

        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue(self::$testRepositoryClass);

        $this->assertEquals(['relation'], $attachedRelationsCount);
    }

    public function testAll()
    {
        $this->mockAll(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount(['relation', 'relation.child_relation'])
            ->force()
            ->all();

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testAllEmptyResult()
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->force()
            ->all();

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testExists()
    {
        $this->mockSelectById(
            'select exists(select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?) as "exists"',
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->exists(['id' => 1]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testExistsBy()
    {
        $this->mockSelectExists(
            'select exists(select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?) as "exists"'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->existsBy('id', 1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testCreate()
    {
        $this->mockCreate(self::$selectResult, null);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->create([
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testCreateOnlyFillable()
    {
        $this->mockCreate(self::$selectResult, Carbon::now());

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->create([
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testInsert()
    {
        $this->mockInsertData();

        $result = self::$testRepositoryClass->insert([
            ['name' => 'test_name_1'],
            ['name' => 'test_name_2'],
            ['name' => 'test_name_3'],
        ]);

        $this->assertTrue($result);
    }

    public function testInsertWithSettableProperties()
    {
        $this->mockInsertData();

        $result = self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->insert([
                ['name' => 'test_name_1'],
                ['name' => 'test_name_2'],
                ['name' => 'test_name_3'],
            ]);

        $this->assertTrue($result);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testInsertWithoutTimestamps()
    {
        $this->mockInsertDataWithoutTimestamps();

        $result = self::$testRepositoryClassWithoutTimestamps->insert([
            [
                'name' => 'test_name_1',
                'created_at' => '1999-01-01',
                'updated_at' => '1999-01-01',
            ],
            [
                'name' => 'test_name_2',
                'created_at' => '1999-01-01',
                'updated_at' => '1999-01-01',
            ],
            [
                'name' => 'test_name_3',
                'created_at' => '1999-01-01',
                'updated_at' => '1999-01-01',
            ],
        ]);

        $this->assertTrue($result);
    }

    public function testInsertWithDifferentTimestampNames()
    {
        $this->mockInsertDataWithDifferentTimestampNames();

        $result = self::$testRepositoryWithDifferentTimestampNames->insert([
            [
                'name' => 'test_name_1',
                'creation_date' => '1999-01-01',
                'updated_date' => '1999-01-01',
            ],
            [
                'name' => 'test_name_2',
                'creation_date' => '1999-01-01',
                'updated_date' => '1999-01-01',
            ],
            [
                'name' => 'test_name_3',
                'creation_date' => '1999-01-01',
                'updated_date' => '1999-01-01',
            ],
        ]);

        $this->assertTrue($result);
    }

    public function testUpdateMany()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "name" = ?, "updated_at" = ? '
            . 'where "test_models"."deleted_at" is not null and "id" = ?',
            ['test_name', Carbon::now(), 1],
            1
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateMany(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdate()
    {
        $this->mockUpdate(self::$selectResult, null);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->update(1, [
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdateOnlyFillable()
    {
        $this->mockUpdate(self::$selectResult, Carbon::now());

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->with('relation')
            ->withCount('relation')
            ->update(1, [
                'name' => 'test_name',
                'updated_at' => null,
            ]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdateDoesntExist()
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->update(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdateOrCreateEntityExists()
    {
        $this->mockUpdateOrCreateEntityExists(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateOrCreate(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdateOrCreateEntityDoesntExist()
    {
        $this->mockUpdateOrCreateEntityDoesntExist(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateOrCreate(1, ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testCount()
    {
        $this->mockSelectById(
            'select count(*) as aggregate from "test_models" where "test_models"."deleted_at" is not null and "id" = ?'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->count(['id' => 1]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testGet()
    {
        $this->mockGet(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->get(['id' => 1]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testGetEmptyResult()
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ?'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->get(['id' => 1]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFirst()
    {
        $this->mockFirst(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->first(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testLast()
    {
        $this->mockLast(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->last(['id' => 1]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFirstEmptyResult()
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->first(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFindBy()
    {
        $this->mockFirstBy(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->findBy('id', 1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFindByEmptyResult()
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->findBy('id', 1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFind()
    {
        $this->mockFind(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->find(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFindEmptyResult()
    {
        $this->mockSelectById(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" = ? limit 1'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->find(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFirstOrCreateEntityExists()
    {
        $this->mockFirstOrCreateEntityExists(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testFirstOrCreateEntityDoesntExists()
    {
        $this->mockFirstOrCreateEntityDoesntExists(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testForceDelete()
    {
        $this->mockDelete('delete from "test_models" where "test_models"."deleted_at" is not null and "id" = ?', [1]);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->delete(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testDelete()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "deleted_at" = ?, "updated_at" = ? '
            . 'where "id" = ?',
            [Carbon::now(), Carbon::now(), 1]
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->with('relation')
            ->withCount('relation')
            ->delete(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testRestore()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "deleted_at" = ?, "updated_at" = ? '
            . 'where "test_models"."deleted_at" is not null and "id" = ? and "test_models"."deleted_at" is not null',
            [null, Carbon::now(), 1]
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->restore(1);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testChunk()
    {
        $this->mockChunk(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->chunk(10, function () {});

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testChunkEmptyResult()
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null order by "id" asc limit 10 offset 0'
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->chunk(10, function () {});

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testForceDeleteByList()
    {
        $this->mockDelete(
            'delete from "test_models" where "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            [1, 2, 3],
            3
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->deleteByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testDeleteByList()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "deleted_at" = ?, "updated_at" = ? where "id" in (?, ?, ?)',
            [Carbon::now(), Carbon::now(), 1, 2, 3]
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->with('relation')
            ->withCount('relation')
            ->deleteByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testRestoreByList()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "deleted_at" = ?, "updated_at" = ? '
            . 'where "test_models"."deleted_at" is not null '
            . 'and "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            [null, Carbon::now(), 1, 2, 3],
            3
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->restoreByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testGetByList()
    {
        $this->mockGetByList(self::$selectResult);

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->getByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testGetByListEmptyResult()
    {
        $this->mockSelect(
            'select "test_models".*, (select count(*) from "relation_models" '
            . 'where "test_models"."id" = "relation_models"."test_model_id") as "relation_count" '
            . 'from "test_models" where "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            [],
            [1, 2, 3]
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->getByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testCountByList()
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from "test_models" '
            . 'where "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            [1, 2, 3]
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->countByList([1, 2, 3]);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testUpdateByList()
    {
        $this->mockUpdateSqlQuery(
            'update "test_models" set "name" = ?, "updated_at" = ? '
            . 'where "test_models"."deleted_at" is not null and "id" in (?, ?, ?)',
            ['test_name', Carbon::now(), 1, 2, 3],
            3
        );

        self::$testRepositoryClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateByList([1, 2, 3], ['name' => 'test_name']);

        $this->assertSettablePropertiesReset(self::$testRepositoryClass);
    }

    public function testTruncate()
    {
        $this->mockTruncate('test_models');

        self::$testRepositoryClass->truncate();
    }

    public function testModelWithoutPrimaryKey()
    {
        $this->expectException(InvalidModelException::class);
        $this->expectExceptionMessage(
            'Model RonasIT\Support\Tests\Support\Mock\Models\TestModelNoPrimaryKey must have primary key.'
        );

        new TestRepositoryNoPrimaryKey();
    }

    public function testGetEntityName()
    {
        $name = self::$testRepositoryClass->getModelName();

        $this->assertEquals('TestModel', $name);
    }
}
