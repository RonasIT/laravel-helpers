<?php

namespace RonasIT\Support\Tests;

use BadMethodCallException;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;
use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;
use RonasIT\Support\Tests\Support\Mock\Services\TestService;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class EntityServiceTest extends TestCase
{
    use SqlMockTrait;

    protected static EntityService $entityServiceClass;
    protected static array $selectResult;

    protected ReflectionProperty $repositoryProperty;
    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $attachedRelationsProperty;
    protected ReflectionProperty $attachedRelationsCountProperty;

    public function setUp(): void
    {
        parent::setUp();

        self::$entityServiceClass ??= new EntityService();

        $this->repositoryProperty = new ReflectionProperty(EntityService::class, 'repository');

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');
        $this->withTrashedProperty = new ReflectionProperty(TestRepository::class, 'withTrashed');
        $this->forceModeProperty = new ReflectionProperty(TestRepository::class, 'forceMode');
        $this->attachedRelationsProperty = new ReflectionProperty(TestRepository::class, 'attachedRelations');
        $this->attachedRelationsCountProperty = new ReflectionProperty(TestRepository::class, 'attachedRelationsCount');

        self::$selectResult ??= $this->getJsonFixture('select_query_result.json');
    }

    public function testSetRepository()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $this->assertTrue($this->repositoryProperty->getValue(self::$entityServiceClass) instanceof TestRepository);
    }

    public function testCallRepositoryMethod()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->getUser();

        $this->assertSame('Correct result', $result);
    }

    public function testCallRepositoryMethodReturnsSelf()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->getFilter();

        $this->assertInstanceOf(EntityService::class, $result);
    }

    public function testCallSettableRepositoryMethodReturnsServiceCopy()
    {
        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->force();

        $this->assertInstanceOf(EntityService::class, $result);
        $this->assertNotSame(self::$entityServiceClass, $result);

        $this->assertTrue($this->forceModeProperty->getValue($this->repositoryProperty->getValue($result)));

        $this->assertSettablePropertiesNotChanged($this->repositoryProperty->getValue(self::$entityServiceClass));
    }

    public function testCallNotExistsRepositoryMethod()
    {
        $className = get_class(self::$entityServiceClass);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Method getSomething does not exists in {$className}.");

        self::$entityServiceClass->setRepository(TestRepository::class);

        self::$entityServiceClass->getSomething();
    }

    public function testFirstOrCreateEntityExists()
    {
        $this->mockFirst(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertFalse($result->wasRecentlyCreated);

        $this->assertSettablePropertiesNotChanged($this->repositoryProperty->getValue(self::$entityServiceClass));
    }

    public function testFirstOrCreateEntityDoesntExist()
    {
        $this->mockFirstOrCreateEntityDoesntExist(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertTrue($result->wasRecentlyCreated);

        $this->assertSettablePropertiesNotChanged($this->repositoryProperty->getValue(self::$entityServiceClass));
    }

    public function testFirstOrCreateWhereOverridesData()
    {
        $this->mockFirstOrCreateWhereOverridesData(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->firstOrCreate(['name' => 'test_name'], [
            'name' => 'overridden_name',
            'json_field' => ['key' => 'value'],
        ]);

        $this->assertTrue($result->wasRecentlyCreated);
    }

    public function testFirstOrCreateWithRelationConditions()
    {
        $this->mockFirstOrCreateWithRelationConditions(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->firstOrCreate(['relation.id' => 2, 'name' => 'test_name'], [
            'json_field' => ['key' => 'value'],
            'unknown_field' => 'value',
        ]);

        $this->assertTrue($result->wasRecentlyCreated);
    }

    public function testFirstOrCreateWhenEntityCreatedConcurrently()
    {
        $this->mockFirstOrCreateWhenEntityCreatedConcurrently(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = self::$entityServiceClass->firstOrCreate(['name' => 'test_name']);

        $this->assertFalse($result->wasRecentlyCreated);
    }

    public function testFirstOrCreateWhenEntityCreatedConcurrentlyInTransaction()
    {
        $this->mockFirstOrCreateWhenEntityCreatedConcurrentlyInTransaction(self::$selectResult);

        self::$entityServiceClass->setRepository(TestRepository::class);

        $result = DB::transaction(fn () => self::$entityServiceClass->firstOrCreate(['name' => 'test_name']));

        $this->assertFalse($result->wasRecentlyCreated);
    }

    public function testFirstOrCreateEntityExistsUsesServiceFirst()
    {
        $this->mockFirst(self::$selectResult);

        $service = app(TestService::class);

        $result = $service
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->firstOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertEquals('first', $result->found_by);

        $this->assertSettablePropertiesNotChanged($this->repositoryProperty->getValue($service));
    }

    public function testFirstOrCreateUsesServiceCreate()
    {
        $this->mockFirstOrCreateUsesServiceCreate(self::$selectResult);

        $result = app(TestService::class)->firstOrCreate(['name' => 'test_name']);

        $this->assertTrue($result->wasRecentlyCreated);
    }

    public function testUpdateOrCreateEntityExistsUsesServiceUpdate()
    {
        $this->mockUpdateOrCreateEntityExistsUsesServiceUpdate(self::$selectResult);

        $service = app(TestService::class);

        $result = $service
            ->withTrashed()
            ->onlyTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->updateOrCreate(['id' => 1], ['name' => 'test_name']);

        $this->assertFalse($result->wasRecentlyCreated);

        $this->assertSettablePropertiesNotChanged($this->repositoryProperty->getValue($service));
    }

    public function testUpdateOrCreateWhenEntityCreatedConcurrently()
    {
        $this->mockUpdateOrCreateWhenEntityCreatedConcurrently(self::$selectResult);

        $result = app(TestService::class)->updateOrCreate(['name' => 'test_name'], [
            'json_field' => ['key' => 'value'],
        ]);

        $this->assertFalse($result->wasRecentlyCreated);
    }

    public function testUpdateOrCreateEntityDoesntExistUsesServiceCreate()
    {
        $this->mockUpdateOrCreateEntityDoesntExistUsesServiceCreate(self::$selectResult);

        $result = app(TestService::class)->updateOrCreate(['name' => 'test_name'], [
            'json_field' => ['key' => 'value'],
        ]);

        $this->assertTrue($result->wasRecentlyCreated);
    }
}
