<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RonasIT\Support\Testing\ModelTestState;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelNonIdPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithCastable;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithCrossAttributeCast;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithCustomCast;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithNativeJsonCasts;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithParameterizedCast;
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

        $customCastFields = $this->getProtectedProperty($reflectionClass, 'castFields', $modelTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $modelTestState);

        $this->assertEquals(['id', 'settings', 'deleted_at'], $customCastFields);

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

    public static function getChangeScenarios(): array
    {
        return [
            'base' => [
                'fixtureDir' => 'changes_equals_fixture',
                'table' => 'test_models',
                'modelClass' => TestModel::class,
            ],
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
            'custom cast' => [
                'fixtureDir' => 'changes_equals_fixture_with_custom_cast',
                'table' => 'test_model_with_custom_casts',
                'modelClass' => TestModelWithCustomCast::class,
            ],
            'parameterized cast' => [
                'fixtureDir' => 'changes_equals_fixture_with_parameterized_cast',
                'table' => 'test_model_with_parameterized_casts',
                'modelClass' => TestModelWithParameterizedCast::class,
            ],
            'castable' => [
                'fixtureDir' => 'changes_equals_fixture_with_castable',
                'table' => 'test_model_with_castables',
                'modelClass' => TestModelWithCastable::class,
            ],
            'cross attribute cast' => [
                'fixtureDir' => 'changes_equals_fixture_with_cross_attribute_cast',
                'table' => 'test_model_with_cross_attribute_casts',
                'modelClass' => TestModelWithCrossAttributeCast::class,
            ],
            'custom primary key' => [
                'fixtureDir' => 'changes_equals_fixture_primary_key',
                'table' => 'test_model_non_id_primary_keys',
                'modelClass' => TestModelNonIdPrimaryKey::class,
                'uniqueKey' => 'name',
            ],
        ];
    }

    #[DataProvider('getChangeScenarios')]
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
