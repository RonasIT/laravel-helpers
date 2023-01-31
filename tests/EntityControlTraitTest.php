<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\DummyConnection;
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

        DummyConnection::mock();
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
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);
        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->exists([]);
    }

    public function testExistsBy()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);
        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->existsBy('id', 1);
    }

    public function testCreate()
    {
        TestModel::saving(fn () => false);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);
        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->create([]);
    }

    public function testUpdateMany()
    {
        DummyConnection::mock(1);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->updateMany(1, []);
    }

    public function testUpdate()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->update(1, []);
    }

    public function testUpdateOrCreate()
    {
        TestModel::saving(fn () => false);

        $mock = $this->mockClass(TestRepository::class, ['setResetSettableProperties', 'resetSettableProperties']);

        $mock->expects($this->exactly(2))->method('setResetSettableProperties');
        $mock->expects($this->exactly(2))->method('resetSettableProperties');

        $mock->updateOrCreate(1, []);
    }

    public function testCount()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->count([]);
    }

    public function testGet()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->get();
    }

    public function testFirst()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->first();
    }

    public function testfindBy()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->first('id', 1);
    }

    public function testFind()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->find(1);
    }

    public function testFirstOrCreate()
    {
        TestModel::saving(fn () => false);

        $mock = $this->mockClass(TestRepository::class, ['setResetSettableProperties', 'resetSettableProperties']);

        $mock->expects($this->exactly(2))->method('setResetSettableProperties');
        $mock->expects($this->exactly(2))->method('resetSettableProperties');

        $mock->firstOrCreate(['id' => 1], []);
    }

    public function testFirstOrCreateEntityExists()
    {
        $mock = $this->mockClass(TestRepository::class, ['setResetSettableProperties', 'first', 'resetSettableProperties']);

        $mock->expects($this->exactly(2))->method('setResetSettableProperties');
        $mock->expects($this->once())->method('first')->willReturn(new TestModel());
        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->firstOrCreate(['id' => 1], []);
    }

    public function testDelete()
    {
        DummyConnection::mock(1);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->delete(1);
    }

    public function testRestore()
    {
        DummyConnection::mock(1);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->restore(1);
    }

    public function testDeleteByList()
    {
        DummyConnection::mock(3);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->deleteByList([1, 2, 3]);
    }

    public function testRestoreByList()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->getByList([1, 2, 3]);
    }

    public function testGetByList()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->getByList([1, 2, 3]);
    }

    public function testCountByList()
    {
        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->countByList([1, 2, 3]);
    }

    public function testUpdateByList()
    {
        DummyConnection::mock(3);

        $mock = $this->mockClass(TestRepository::class, ['resetSettableProperties']);

        $mock->expects($this->once())->method('resetSettableProperties');

        $mock->updateByList([1, 2, 3], []);
    }
}