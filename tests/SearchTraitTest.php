<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait;

    protected TestRepository $testRepositoryClass;
    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $queryProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->testRepositoryClass = new TestRepository();

        $this->onlyTrashedProperty = new ReflectionProperty(TestRepository::class, 'onlyTrashed');
        $this->onlyTrashedProperty->setAccessible('pubic');

        $this->queryProperty = new ReflectionProperty(TestRepository::class, 'query');
        $this->queryProperty->setAccessible('pubic');
    }

    public function testSearchQueryWithOnlyTrashed()
    {
        $this->testRepositoryClass->searchQuery(['only_trashed' => true]);

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);

        $sql = $this->queryProperty->getValue($this->testRepositoryClass)->toSql();

        $this->assertEquals(true, $onlyTrashed);

        $this->assertEqualsFixture('search_query_with_only_trashed_sql.json', $sql);
    }
}