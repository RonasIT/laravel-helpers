<?php

namespace RonasIT\Support\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use ReflectionMethod;
use RonasIT\Support\Tests\Support\Mock\TestRepository;
use ReflectionProperty;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class SearchTraitTest extends HelpersTestCase
{
    use SqlMockTrait;

    protected TestRepository $testRepositoryClass;

    protected ReflectionProperty $onlyTrashedProperty;
    protected ReflectionProperty $withTrashedProperty;
    protected ReflectionProperty $forceModeProperty;
    protected ReflectionProperty $attachedRelationsProperty;
    protected ReflectionProperty $attachedRelationsCountProperty;
    protected ReflectionProperty $queryProperty;
    protected ReflectionProperty $shouldSettablePropertiesBeResetProperty;

    protected ReflectionMethod $setAdditionalReservedFiltersMethod;

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

        $this->attachedRelationsCountProperty = new ReflectionProperty(
            TestRepository::class,
            'attachedRelationsCount'
        );
        $this->attachedRelationsCountProperty->setAccessible(true);

        $this->shouldSettablePropertiesBeResetProperty = new ReflectionProperty(
            TestRepository::class,
            'shouldSettablePropertiesBeReset'
        );
        $this->shouldSettablePropertiesBeResetProperty->setAccessible(true);

        $this->queryProperty = new ReflectionProperty(TestRepository::class, 'query');
        $this->queryProperty->setAccessible(true);

        $reflectionClass = new ReflectionClass(TestRepository::class);
        $this->setAdditionalReservedFiltersMethod = $reflectionClass->getMethod('setAdditionalReservedFilters');
        $this->setAdditionalReservedFiltersMethod->setAccessible(true);

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

        $this->assertTrue($onlyTrashed);
        $this->assertFalse($withTrashed);
        $this->assertTrue($forceMode);
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

        $this->assertFalse($withTrashed);
    }

    public function testGetSearchResultAggregateIsNull()
    {
        $this->mockSelectWithAggregate(
            'select count(*) as aggregate from `test_models` where `test_models`.`deleted_at` is null',
            [],
            null
        );

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

        $this->assertTrue($onlyTrashed);
        $this->assertFalse($withTrashed);
        $this->assertTrue($forceMode);
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
        Config::set('database.default', 'pgsql');

        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithCustomQuery($this->selectResult);

        $this->testRepositoryClass
            ->searchQuery([
                'query' => 'search_string'
            ])
            ->filterByQuery(['query_field', 'another_query_field'], "'%' || unaccent('{{ value }}') || '%'")
            ->getSearchResults();
    }

    public function testSearchQueryWithRelations()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithRelations($this->selectResult);

        $this->setAdditionalReservedFiltersMethod->invokeArgs($this->testRepositoryClass, [
            'relation_name'
        ]);

        $this->testRepositoryClass
            ->searchQuery([
                'query' => 'search_string',
                'order_by' => 'relation.id',
                'relation_name' => 'some_value'
            ])
            ->filterByQuery(['query_field', 'relation.another_query_field'])
            ->filterBy('relation.name', 'relation_name')
            ->getSearchResults();
    }

    public function testSearchQueryWithFilters()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithFilters($this->selectResult);

        $this->testRepositoryClass
            ->searchQuery([
                'user_id_in_list' => [1, 2],
                'user_id_not_in_list' => [3, 4],
                'name' => 'text_name',
                'date_gte' => Carbon::now(),
                'date_lte' => Carbon::now(),
                'created_at_from' => Carbon::now(),
                'created_at_to' => Carbon::now(),
                'updated_at_gt' => Carbon::now(),
                'updated_at_lt' => Carbon::now(),
            ])
            ->getSearchResults();
    }

    public function testSearchQueryWithFiltersFunctions()
    {
        $this->shouldSettablePropertiesBeResetProperty->setValue($this->testRepositoryClass, false);

        $this->mockGetSearchResultWithFilters($this->selectResult);

        $this->testRepositoryClass
            ->searchQuery([
                'user_id_in_list' => [1, 2],
                'user_id_not_in_list' => [3, 4],
                'name' => 'text_name',
            ])
            ->filterMoreOrEqualThan('date', Carbon::now())
            ->filterLessOrEqualThan('date', Carbon::now())
            ->filterMoreOrEqualThan('created_at', Carbon::now())
            ->filterLessOrEqualThan('created_at', Carbon::now())
            ->filterMoreThan('updated_at', Carbon::now())
            ->filterLessThan('updated_at', Carbon::now())
            ->getSearchResults();
    }
}