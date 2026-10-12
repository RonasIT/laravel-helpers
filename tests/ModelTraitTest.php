<?php

namespace RonasIT\Support\Tests;

use BadMethodCallException;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Models\GetFieldsTestModel;
use RonasIT\Support\Tests\Support\Mock\Models\GetFieldsTestModelNoPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Models\GetFieldsTestModelWithCustomTimestamps;
use RonasIT\Support\Tests\Support\Mock\Models\GetFieldsTestModelWithoutTimestamps;
use RonasIT\Support\Tests\Support\Mock\Models\RelationModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithDependentAccessors;
use RonasIT\Support\Tests\Support\Traits\ModelTestTrait;

class ModelTraitTest extends TestCase
{
    use ModelTestTrait;

    public static function getGetFieldsData(): array
    {
        return [
            [
                'model' => GetFieldsTestModel::class,
                'expected' => ['id', 'name', 'json_field', '*', 'created_at', 'updated_at'],
            ],
            [
                'model' => GetFieldsTestModelWithoutTimestamps::class,
                'expected' => ['id', 'name', 'json_field', '*'],
            ],
            [
                'model' => GetFieldsTestModelNoPrimaryKey::class,
                'expected' => [null, 'name', 'json_field', '*', 'created_at', 'updated_at'],
            ],
            [
                'model' => GetFieldsTestModelWithCustomTimestamps::class,
                'expected' => ['id', 'name', 'creation_date', '*', 'created_at', 'updated_at'],
            ],
        ];
    }

    #[DataProvider('getGetFieldsData')]
    public function testGetFields(string $model, array $expected)
    {
        $result = $model::getFields();

        $this->assertEquals($expected, $result);
    }

    public function testGetAllFieldsWithTable()
    {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_models')
            ->andReturn(['id', 'name', 'json_field', 'castable_field', 'created_at', 'updated_at', 'deleted_at']);

        $model = new TestModel();

        $result = $model->getAllFieldsWithTable();

        $this->assertEquals([
            'test_models.id',
            'test_models.name',
            'test_models.json_field',
            'test_models.castable_field',
            'test_models.created_at',
            'test_models.updated_at',
            'test_models.deleted_at',
        ], $result);
    }

    public function testScopeAddFieldsToSelectWithoutFields()
    {
        $query = TestModel::query();

        $query->addFieldsToSelect();

        $this->assertEquals('select "test_models".* from "test_models" where "test_models"."deleted_at" is null', $query->toSql());
    }

    public function testScopeAddFieldsToSelectWithFields()
    {
        $query = TestModel::query();

        $query->addFieldsToSelect(['test_models.id', 'test_models.name']);

        $this->assertEquals(
            'select "test_models".*, "test_models"."id", "test_models"."name" from "test_models" where "test_models"."deleted_at" is null',
            $query->toSql(),
        );
    }

    public function testScopeAddFieldsToSelectPreservesExistingColumns()
    {
        $query = TestModel::query()->select('test_models.id');

        $query->addFieldsToSelect(['test_models.name']);

        $this->assertEquals(
            'select "test_models"."id", "test_models"."name" from "test_models" where "test_models"."deleted_at" is null',
            $query->toSql(),
        );
    }

    public function testScopeOrderByRelated()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.name');

        $this->assertEquals(
            'select "test_models".*, (select "name" from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" order by "id" asc limit 1) as "relation_name" from "test_models" where "test_models"."deleted_at" is null order by "relation_name" desc',
            $query->toSql(),
        );
    }

    public function testScopeOrderByRelatedAsc()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.name', 'ASC');

        $this->assertEquals(
            'select "test_models".*, (select "name" from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" order by "id" asc limit 1) as "relation_name" from "test_models" where "test_models"."deleted_at" is null order by "relation_name" asc',
            $query->toSql(),
        );
    }

    public function testScopeOrderByRelatedWithAsField()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.name', asField: 'sort_field');

        $this->assertEquals(
            'select "test_models".*, (select "name" from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" order by "id" asc limit 1) as "sort_field" from "test_models" where "test_models"."deleted_at" is null order by "sort_field" desc',
            $query->toSql(),
        );
    }

    public function testScopeOrderByRelatedWithMinStrategy()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.name', manyToManyStrategy: 'min');

        $this->assertEquals(
            'select "test_models".*, (select "name" from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" order by "id" desc limit 1) as "relation_name" from "test_models" where "test_models"."deleted_at" is null order by "relation_name" desc',
            $query->toSql(),
        );
    }

    public function testScopeOrderByRelatedNestedRelations()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.child_relation.name');

        $this->assertEquals(
            'select "test_models".*, (select (select "name" from "child_relation_models" where "relation_models"."id" = "child_relation_models"."relation_model_id" order by "id" asc limit 1) as "relation_child_relation_name" from "relation_models" where "test_models"."id" = "relation_models"."test_model_id" order by "id" asc limit 1) as "relation_child_relation_name" from "test_models" where "test_models"."deleted_at" is null order by "relation_child_relation_name" desc',
            $query->toSql(),
        );
    }

    public function testLazyLoadingDisabled()
    {
        $model = new TestModel();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageCompat(
            "Attempting to lazy-load relation 'relation' on model '" . TestModel::class . "'. "
            . 'See property $disableLazyLoading.',
        );

        $model->relation;
    }

    public static function getWasExchangedData(): array
    {
        return [
            [
                'origin' => 'old',
                'updated' => 'new',
                'result' => true,
            ],
            [
                'origin' => 'old',
                'updated' => null,
                'result' => false,
            ],
            [
                'origin' => null,
                'updated' => 'new',
                'result' => false,
            ],
        ];
    }

    #[DataProvider('getWasExchangedData')]
    public function testWasExchanged(?string $origin, ?string $updated, bool $result)
    {
        $model = $this->createModelWithTransition($origin, $updated);

        $this->assertSame($result, $model->wasExchanged('name'));
    }

    public static function getWasFilledData(): array
    {
        return [
            [
                'before' => null,
                'after' => 'new',
                'expected' => true,
            ],
            [
                'before' => null,
                'after' => '',
                'expected' => true,
            ],
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => false,
            ],
            [
                'before' => 'old',
                'after' => null,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasFilledData')]
    public function testWasFilled(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasFilled('name'));
    }

    public static function getWasClearedData(): array
    {
        return [
            [
                'before' => 'old',
                'after' => null,
                'expected' => true,
            ],
            [
                'before' => null,
                'after' => 'new',
                'expected' => false,
            ],
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasClearedData')]
    public function testWasCleared(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasCleared('name'));
    }

    public static function getCastableFieldTransitionData(): array
    {
        return [
            [
                'before' => ['key' => 'old'],
                'after' => ['key' => 'new'],
                'expected' => [
                    'wasExchanged' => true,
                    'wasFilled' => false,
                    'wasCleared' => false,
                ],
            ],
            [
                'before' => null,
                'after' => ['key' => 'new'],
                'expected' => [
                    'wasExchanged' => false,
                    'wasFilled' => true,
                    'wasCleared' => false,
                ],
            ],
            [
                'before' => ['key' => 'old'],
                'after' => null,
                'expected' => [
                    'wasExchanged' => false,
                    'wasFilled' => false,
                    'wasCleared' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getCastableFieldTransitionData')]
    public function testCastableFieldTransition(?array $before, ?array $after, array $expected)
    {
        $model = $this->createModelWithTransition($before, $after, 'json_field');

        $this->assertSame($expected['wasExchanged'], $model->wasExchanged('json_field'));
        $this->assertSame($expected['wasFilled'], $model->wasFilled('json_field'));
        $this->assertSame($expected['wasCleared'], $model->wasCleared('json_field'));
    }

    public static function getTransitionInUpdatedAndSavedEventsData(): array
    {
        return [
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => [
                    'wasExchanged' => true,
                    'wasFilled' => false,
                    'wasCleared' => false,
                ],
            ],
            [
                'before' => null,
                'after' => 'new',
                'expected' => [
                    'wasExchanged' => false,
                    'wasFilled' => true,
                    'wasCleared' => false,
                ],
            ],
            [
                'before' => 'old',
                'after' => null,
                'expected' => [
                    'wasExchanged' => false,
                    'wasFilled' => false,
                    'wasCleared' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getTransitionInUpdatedAndSavedEventsData')]
    public function testTransitionInUpdatedAndSavedEvents(?string $before, ?string $after, array $expected)
    {
        $model = $this->createModelWithTransition($before, $after, isSaveFinished: false);

        $this->assertSame($expected['wasExchanged'], $model->wasExchanged('name'));
        $this->assertSame($expected['wasFilled'], $model->wasFilled('name'));
        $this->assertSame($expected['wasCleared'], $model->wasCleared('name'));
        $this->assertSame($before, $model->getPreviousValue('name'));
    }

    public function testTransitionIgnoresUnsavedChanges()
    {
        $model = $this->createModelWithTransition('old', 'new');

        $model->name = null;

        $this->assertTrue($model->wasExchanged('name'));
        $this->assertFalse($model->wasCleared('name'));
        $this->assertSame('old', $model->getPreviousValue('name'));
    }

    public function testTransitionOfMissingAttributeToNull()
    {
        $model = new TestModel();
        $model->syncOriginal();
        $model->forceFill(['name' => null]);
        $model->syncChanges();
        $model->syncOriginal();

        $this->assertFalse($model->wasExchanged('name'));
        $this->assertFalse($model->wasFilled('name'));
        $this->assertFalse($model->wasCleared('name'));
    }

    public function testNoChange()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'same']);
        $model->syncOriginal();

        $this->assertFalse($model->wasExchanged('name'));
        $this->assertFalse($model->wasFilled('name'));
        $this->assertFalse($model->wasCleared('name'));
    }

    public function testGetPreviousValue()
    {
        $model = $this->createModelWithTransition('old', 'new');

        $this->assertSame('old', $model->getPreviousValue('name'));
    }

    public function testGetPreviousValueReturnsOldValueWhenCurrentValueIsCached()
    {
        $model = $this->createModelWithTransition(['key' => 'old'], ['key' => 'new'], 'custom_cast_field');

        $this->assertEquals((object) ['key' => 'new'], $model->custom_cast_field);
        $this->assertEquals((object) ['key' => 'old'], $model->getPreviousValue('custom_cast_field'));
    }

    public function testGetPreviousValueReturnsOldValueWhenCurrentAccessorValueIsCached()
    {
        $model = $this->createModelWithTransition('old', 'new', 'meta', new TestModelWithDependentAccessors());

        $this->assertEquals((object) ['value' => 'new'], $model->meta);
        $this->assertEquals((object) ['value' => 'old'], $model->getPreviousValue('meta'));
    }

    public function testGetPreviousValueAccessorSeesOtherAttributes()
    {
        $model = (new TestModelWithDependentAccessors())->forceFill(['surname' => 'Smith']);

        $model = $this->createModelWithTransition('Old', 'New', 'name', $model);

        $this->assertSame('Old Smith', $model->getPreviousValue('name'));
    }

    public function testGetPreviousValueAccessorSeesLoadedRelations()
    {
        $model = $this->createModelWithTransition('old', 'new', 'title', new TestModelWithDependentAccessors());

        $model->setRelation('relation', collect([new RelationModel(), new RelationModel()]));

        $this->assertSame('old (2)', $model->getPreviousValue('title'));
    }

    public function testGetPreviousValueAccessorSeesModelState()
    {
        $model = (new TestModelWithDependentAccessors())->setCurrency('EUR');

        $model = $this->createModelWithTransition('90', '100', 'price', $model);

        $this->assertSame('90 EUR', $model->getPreviousValue('price'));
    }

    public function testGetPreviousValueAccessorSeesConsistentOriginal()
    {
        $model = $this->createModelWithTransition('draft', 'published', 'status', new TestModelWithDependentAccessors());

        $this->assertSame('draft', $model->getPreviousValue('status'));
    }

    public function testGetPreviousValueAppliesCastsAddedAtRuntime()
    {
        $model = (new TestModel())->mergeCasts(['name' => 'array']);

        $model = $this->createModelWithTransition(['key' => 'old'], ['key' => 'new'], 'name', $model);

        $this->assertSame(['key' => 'old'], $model->getPreviousValue('name'));
    }

    public function testGetPreviousValueAccessorReadingPreviousValueOfItself()
    {
        $model = $this->createModelWithTransition('v1', 'v2', 'history', new TestModelWithDependentAccessors());

        $this->assertSame('v2 (was v1)', $model->history);
    }

    public function testGetPreviousValueOfNumericColumn()
    {
        $model = $this->createModelWithTransition('old', 'new', '2023');

        $this->assertSame('old', $model->getPreviousValue('2023'));
    }

    public function testGetPreviousValueDoesNotChangeModel()
    {
        $model = $this->createModelWithTransition(['key' => 'old'], ['key' => 'new'], 'custom_cast_field');

        $current = $model->custom_cast_field;
        $original = $model->getRawOriginal();

        $model->getPreviousValue('custom_cast_field');

        $this->assertSame($current, $model->custom_cast_field);
        $this->assertSame($original, $model->getRawOriginal());
    }

    public static function getIsSaveFinishedData(): array
    {
        return [
            ['isSaveFinished' => true],
            ['isSaveFinished' => false],
        ];
    }

    #[DataProvider('getIsSaveFinishedData')]
    public function testGetPreviousValueAccessorSeesAttributeChangedInSameSave(bool $isSaveFinished)
    {
        $model = new TestModelWithDependentAccessors();

        $model->forceFill(['name' => 'Old']);
        $model->syncOriginal();
        $model->forceFill(['name' => 'New', 'surname' => 'Smith']);
        $model->syncChanges();

        if ($isSaveFinished) {
            $model->syncOriginal();
        }

        $this->assertSame('Old Smith', $model->getPreviousValue('name'));
    }

    public function testGetPreviousValueInUpdatedAndSavedEventsAppliesCast()
    {
        $model = $this->createModelWithTransition(['key' => 'old'], ['key' => 'new'], 'custom_cast_field', isSaveFinished: false);

        $this->assertEquals((object) ['key' => 'old'], $model->getPreviousValue('custom_cast_field'));
    }

    public function testGetPreviousValueReturnsNullWhenNoPreviousValue()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'value']);
        $model->syncOriginal();

        $this->assertNull($model->getPreviousValue('name'));
    }
}
