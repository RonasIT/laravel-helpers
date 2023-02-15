<?php

namespace RonasIT\Support\Tests;

use Mpyw\LaravelDatabaseMock\Facades\DBMock;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;
use ReflectionMethod;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait;

    protected TestRepository $testRepositoryClass;
    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $queryProperty;

    protected ReflectionMethod $postQueryHookMethod;
    protected ReflectionMethod $resetSettableProperties;

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

        $this->queryProperty = new ReflectionProperty(TestRepository::class, 'query');
        $this->queryProperty->setAccessible('pubic');

        $this->postQueryHookMethod = new ReflectionMethod($this->testRepositoryClass, 'postQueryHook');
        $this->postQueryHookMethod->setAccessible('pubic');

        $this->resetSettableProperties = new ReflectionMethod($this->testRepositoryClass, 'resetSettableProperties');
        $this->resetSettableProperties->setAccessible('pubic');
    }

    public function testSearchQueryWithOnlyTrashed()
    {
        $this->testRepositoryClass->searchQuery(['only_trashed' => true]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $sql = $this->queryProperty->getValue($this->testRepositoryClass)->toSql();

        $this->assertEquals(true, $onlyTrashed);

        $this->assertEqualsFixture('search_query_with_only_trashed_sql.json', $sql);
    }

    public function testGetSearchResultWithAll()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select * from `test_models` where `test_models`.`deleted_at` is null order by `id` asc')
            ->whenFetchAllCalled();

        $this->testRepositoryClass->searchQuery(['all' => true])->getSearchResults();
    }

    public function testGetSearchResult()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is null')
            ->whenFetchAllCalled();

        $this->testRepositoryClass->searchQuery()->getSearchResults();
    }

    public function testGetSearchResultWithOnlyTrash()
    {
        $pdo = DBMock::mockPdo();
        $pdo
            ->shouldSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is not null')
            ->whenFetchAllCalled();

        $this->testRepositoryClass->searchQuery(['only_trashed' => true])->getSearchResults();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testPostQueryHookMethod()
    {
        $this->testRepositoryClass->onlyTrashed();
        $this->testRepositoryClass->force();
        $this->testRepositoryClass->withTrashed();

        $this->postQueryHookMethod->invoke($this->testRepositoryClass);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
        $this->assertEquals(false, $withTrashed);
        $this->assertEquals(false, $forceMode);
    }

    public function testPostQueryHookMethodPropertyFalse()
    {
        $this->testRepositoryClass->onlyTrashed();
        $this->testRepositoryClass->force();
        $this->testRepositoryClass->withTrashed();

        $this->resetSettableProperties->invoke($this->testRepositoryClass, false);

        $this->postQueryHookMethod->invoke($this->testRepositoryClass);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
        $this->assertEquals(true, $withTrashed);
        $this->assertEquals(true, $forceMode);
    }
}