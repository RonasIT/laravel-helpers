<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\TestRepository;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use ReflectionProperty;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class SearchTraitTest extends HelpersTestCase
{
    use MockTrait, SqlMockTrait;

    protected TestRepository $testRepositoryClass;

    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $attachedRelationsProperty;
    protected ReflectionProperty $attachedRelationsCountProperty;
    protected ReflectionProperty $queryProperty;
    protected ReflectionProperty $shouldSettablePropertiesBeResetProperty;

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

        $this->shouldSettablePropertiesBeResetProperty = new ReflectionProperty(TestRepository::class, 'shouldSettablePropertiesBeReset');
        $this->shouldSettablePropertiesBeResetProperty->setAccessible('pubic');

        $this->queryProperty = new ReflectionProperty(TestRepository::class, 'query');
        $this->queryProperty->setAccessible('pubic');

        $this->selectResult = $this->getJsonFixture('select_query_result.json');
    }

    public function testSearchQuery()
    {
        $this->testRepositoryClass
            ->force()
            ->searchQuery([
                'with_trashed' => true,
                'only_trashed' => true,
                'with' => ['relation'],
                'with_count' => ['relation']
            ]);

        $sql = $this->queryProperty->getValue($this->testRepositoryClass)->toSql();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);
        $attachedRelations = $this->attachedRelationsProperty->getValue($this->testRepositoryClass);
        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
        $this->assertEquals(false, $withTrashed);
        $this->assertEquals(true, $forceMode);
        $this->assertEquals(['relation'], $attachedRelations);
        $this->assertEquals(['relation'], $attachedRelationsCount);

        $this->assertEqualsFixture('search_query_sql.json', $sql);
    }

    public function testGetSearchResultWithAll()
    {
        $this->mockSelect('select * from `test_models` where `test_models`.`deleted_at` is null order by `id` asc');

        $this->testRepositoryClass->searchQuery(['all' => true])->getSearchResults();
    }

    public function testGetSearchResult()
    {
        $this->mockGetSearchResult($this->selectResult);

        $this->testRepositoryClass
            ->force()
            ->searchQuery([
                'with_trashed' => true,
                'only_trashed' => true,
                'with' => 'relation',
                'with_count' => 'relation'
            ])
            ->getSearchResults();

        $this->assertSettablePropertiesReset($this->testRepositoryClass);
    }

    public function testGetSearchResultWithTrashed()
    {
        $this->mockGetSearchResultWithTrashed();

        $this->testRepositoryClass->searchQuery(['with_trashed' => true])->getSearchResults();

        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(false, $withTrashed);
    }

    public function testGetSearchResultAggregateIsNull()
    {
        $this->mockSelect('select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is null', [], [['aggregate' => null]]);

        $this->testRepositoryClass->searchQuery()->getSearchResults();
    }

    public function testPostQueryHookMethodPropertyFalse()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResult($this->selectResult);

        $this->testRepositoryClass
            ->onlyTrashed()
            ->withTrashed()
            ->force()
            ->with('relation')
            ->withCount('relation')
            ->searchQuery()
            ->getSearchResults();

        $onlyTrashed = $this->onlyTrashedProperty->getValue($this->testRepositoryClass);
        $withTrashed = $this->withTrashedProperty->getValue($this->testRepositoryClass);
        $forceMode = $this->forceModeProperty->getValue($this->testRepositoryClass);
        $attachedRelations = $this->attachedRelationsProperty->getValue($this->testRepositoryClass);
        $attachedRelationsCount = $this->attachedRelationsCountProperty->getValue($this->testRepositoryClass);

        $this->assertEquals(true, $onlyTrashed);
        $this->assertEquals(false, $withTrashed);
        $this->assertEquals(true, $forceMode);
        $this->assertEquals(['relation'], $attachedRelations);
        $this->assertEquals(['relation'], $attachedRelationsCount);
    }

    public function testSearchQueryWithQuery()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithQuery($this->selectResult);

        $this->testRepositoryClass
            ->searchQuery([
                'query' => 'search_string'
            ])
            ->filterByQuery(['query_field', 'another_query_field'])
            ->getSearchResults();
    }

    public function testSearchQueryWithMaskedQuery()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithCustomQuery($this->selectResult);

        $this->testRepositoryClass
            ->searchQuery([
                'query' => 'search_string'
            ])
            ->filterByQuery(['query_field', 'another_query_field'], "'%' || unaccent('{{ value }}') || '%'")
            ->getSearchResults();
    }
}