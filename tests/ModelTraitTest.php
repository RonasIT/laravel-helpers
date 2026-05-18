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

        $this->assertStringContainsString('"test_models".*', $query->toSql());
    }

    public function testScopeAddFieldsToSelectWithFields()
    {
        $query = TestModel::query();

        $query->addFieldsToSelect(['test_models.id', 'test_models.name']);

        $sql = $query->toSql();

        $this->assertStringContainsString('"test_models".*', $sql);
        $this->assertStringContainsString('"test_models"."id"', $sql);
        $this->assertStringContainsString('"test_models"."name"', $sql);
    }

    public function testScopeAddFieldsToSelectPreservesExistingColumns()
    {
        $query = TestModel::query()->select('test_models.id');

        $query->addFieldsToSelect(['test_models.name']);

        $sql = $query->toSql();

        $this->assertStringContainsString('"test_models"."id"', $sql);
        $this->assertStringContainsString('"test_models"."name"', $sql);
        $this->assertStringNotContainsString('"test_models".*', $sql);
    }

    public function testScopeOrderByRelated()
    {
        $query = TestModel::query();

        $query->orderByRelated('relation.name');

        $sql = $query->toSql();

        $this->assertStringContainsString('order by', $sql);
        $this->assertStringContainsString('"relation_name"', $sql);
        $this->assertStringContainsString('"relation_models"', $sql);
    }

    public static function getWasExchangedData(): array
    {
        return [
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => true,
            ],
            [
                'before' => 'old',
                'after' => null,
                'expected' => false,
            ],
            [
                'before' => null,
                'after' => 'new',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasExchangedData')]
    public function testWasExchanged(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasExchanged('name'));
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

    protected function createModelWithTransition(?string $before, ?string $after): TestModel
    {
        $model = new TestModel();
        $model->forceFill(['name' => $before]);
        $model->syncOriginal();
        $model->forceFill(['name' => $after]);
        $model->syncChanges();
        $model->syncOriginal();

        return $model;
    }
}
