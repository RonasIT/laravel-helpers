<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RonasIT\Support\Testing\ModelTestState;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelNonIdPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithCustomJsonCast;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithCustomNonJsonCast;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithNativeJsonCasts;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithPrimitiveCasts;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class ModelTestStateTest extends TestCase
{
    use TableTestStateMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;

        putenv('FAIL_EXPORT_JSON=false');
    }

    public function testInitialization(): void
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset'));
        $originRecords = collect($this->getJsonFixture('initialization/origin_records'));

        $this->mockGettingDataset($datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $reflectionClass = new ReflectionClass($modelTestState);

        $jsonFields = $this->getProtectedProperty($reflectionClass, 'jsonFields', $modelTestState);
        $classCastFields = $this->getProtectedProperty($reflectionClass, 'classCastFields', $modelTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $modelTestState);

        $this->assertEquals(['json_field'], $jsonFields);
        $this->assertEquals(['custom_cast_field', 'castable_field'], $classCastFields);

        $this->assertEquals($originRecords, $state);
    }

    public static function getInitializationViaPrepareModelTestStateFilters(): array
    {
        return [
            [
                'testCaseGlobalExportMode' => true,
            ],
            [
                'testCaseGlobalExportMode' => false,
            ],
        ];
    }

    #[DataProvider('getInitializationViaPrepareModelTestStateFilters')]
    public function testInitializationViaPrepareTableTestState(bool $testCaseGlobalExportMode): void
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset'));
        $this->mockGettingDataset($datasetMock);

        $actualGlobalExportModeValue = $this->mockTestStateCreationSetGlobalExportMode('prepareModelTestState', TestModel::class, $testCaseGlobalExportMode);

        $this->assertEquals($actualGlobalExportModeValue, $testCaseGlobalExportMode);
    }

    public static function getAssertChangesData(): array
    {
        return [
            'primitive casts' => [
                'fixtureDir' => 'changes_equals_fixture_with_primitive_casts',
                'table' => 'test_model_with_primitive_casts',
                'modelClass' => TestModelWithPrimitiveCasts::class,
            ],
            'native json casts' => [
                'fixtureDir' => 'changes_equals_fixture_with_native_json_casts',
                'table' => 'test_model_with_native_json_casts',
                'modelClass' => TestModelWithNativeJsonCasts::class,
            ],
            'custom json cast' => [
                'fixtureDir' => 'changes_equals_fixture_with_custom_json_cast',
                'table' => 'test_model_with_custom_json_casts',
                'modelClass' => TestModelWithCustomJsonCast::class,
            ],
            'custom non-json cast' => [
                'fixtureDir' => 'changes_equals_fixture_with_custom_non_json_cast',
                'table' => 'test_model_with_custom_non_json_casts',
                'modelClass' => TestModelWithCustomNonJsonCast::class,
            ],
            'custom primary key' => [
                'fixtureDir' => 'changes_equals_fixture_primary_key',
                'table' => 'test_model_non_id_primary_keys',
                'modelClass' => TestModelNonIdPrimaryKey::class,
                'uniqueKey' => 'name',
            ],
        ];
    }

    #[DataProvider('getAssertChangesData')]
    public function testAssertChanges(
        string $fixtureDir,
        string $table,
        string $modelClass,
        string $uniqueKey = 'id',
    ): void {
        $initialDatasetMock = collect($this->getJsonFixture("{$fixtureDir}/initial_dataset"));
        $changedDatasetMock = collect($this->getJsonFixture("{$fixtureDir}/changed_dataset"));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, $table, $uniqueKey);

        $modelTestState = new ModelTestState($modelClass);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture');
    }

    public function testAssertNoChanges(): void
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset'));

        $this->mockGettingDatasetForChanges($datasetMock, $datasetMock, 'test_models');

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertNotChanged();
    }
}
