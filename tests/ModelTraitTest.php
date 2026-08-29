<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelNoPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithDifferentTimestampNames;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithoutTimestamps;

class ModelTraitTest extends TestCase
{
    public static function getGetFieldsData(): array
    {
        return [
            [
                'model' => TestModel::class,
                'expected' => ['id', 'name', 'json_field', 'castable_field', '*', 'created_at', 'updated_at'],
            ],
            [
                'model' => TestModelWithoutTimestamps::class,
                'expected' => ['id', 'name', 'json_field', 'castable_field', 'created_at', '*'],
            ],
            [
                'model' => TestModelNoPrimaryKey::class,
                'expected' => [null, 'name', 'json_field', 'castable_field', '*', 'created_at', 'updated_at'],
            ],
            [
                'model' => TestModelWithDifferentTimestampNames::class,
                'expected' => ['id', 'name', 'json_field', 'castable_field', 'creation_date', '*', 'created_at', 'updated_at'],
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

    public function testNoChange()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'same']);
        $model->syncOriginal();

        $this->assertFalse($model->wasExchanged('name'));
        $this->assertFalse($model->wasFilled('name'));
        $this->assertFalse($model->wasCleared('name'));
    }

    public function testOrigin()
    {
        $model = $this->createModelWithTransition('old', 'new');

        $this->assertSame('old', $model->origin('name'));
    }

    public function testOriginReturnsNullWhenNoPreviousValue()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'value']);
        $model->syncOriginal();

        $this->assertNull($model->origin('name'));
    }

    protected function createModelWithTransition(?string $originName, ?string $newName): TestModel
    {
        $model = new TestModel();
        $model->forceFill(['name' => $originName]);
        $model->syncOriginal();
        $model->forceFill(['name' => $newName]);
        $model->syncChanges();
        $model->syncOriginal();

        return $model;
    }
}
