<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\DummyConnection;
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

    protected ReflectionMethod $resetSettablePropertiesMethod;
    protected ReflectionMethod $setResetSettablePropertiesMethod;

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

        $this->resetSettablePropertiesMethod = new ReflectionMethod($this->testRepositoryClass, 'resetSettableProperties');
        $this->resetSettablePropertiesMethod->setAccessible('pubic');

        $this->setResetSettablePropertiesMethod = new ReflectionMethod($this->testRepositoryClass, 'setResetSettableProperties');
        $this->setResetSettablePropertiesMethod->setAccessible('pubic');
    }

    public function testSearchQueryWithOnlyTrashed()
    {
        $this->testRepositoryClass->searchQuery(['only_trashed' => true]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $sql = $this->queryProperty->getValue($this->testRepositoryClass)->toSql();

        $this->assertEquals(true, $onlyTrashed);

        $this->assertEqualsFixture('search_query_with_only_trashed_sql.json', $sql);
    }

    public function testGetSearchResult()
    {
        DummyConnection::mock();

        $this->testRepositoryClass->searchQuery(['only_trashed' => true])->getSearchResults();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
    }

    public function testResetSettableProperties()
    {
        $this->testRepositoryClass->onlyTrashed();
        $this->testRepositoryClass->force();
        $this->testRepositoryClass->withTrashed();

        $this->resetSettablePropertiesMethod->invoke($this->testRepositoryClass);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $onlyTrashed);
        $this->assertEquals(false, $withTrashed);
        $this->assertEquals(false, $forceMode);
    }

    public function testResetSettablePropertiesPropertyFalse()
    {
        $this->testRepositoryClass->onlyTrashed();
        $this->testRepositoryClass->force();
        $this->testRepositoryClass->withTrashed();

        $this->setResetSettablePropertiesMethod->invoke($this->testRepositoryClass, false);

        $this->resetSettablePropertiesMethod->invoke($this->testRepositoryClass);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
        $this->assertEquals(true, $withTrashed);
        $this->assertEquals(true, $forceMode);
    }
}