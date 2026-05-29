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
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $originRecords = collect($this->getJsonFixture('initialization/origin_records.json'));

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
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $actualGlobalExportModeValue = $this->mockTestStateCreationSetGlobalExportMode('prepareModelTestState', TestModel::class, $testCaseGlobalExportMode);

        $this->assertEquals($actualGlobalExportModeValue, $testCaseGlobalExportMode);
    }

    public function testAssertChanges(): void
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_models');

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithCustomPrimaryKey(): void
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture_primary_key/initial_dataset'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture_primary_key/changed_dataset'));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_non_id_primary_keys', 'name');

        $modelTestState = new ModelTestState(TestModelNonIdPrimaryKey::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture_primary_key');
    }

    public function testAssertChangesWithPrimitiveCasts(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_primitive_casts/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_primitive_casts/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_primitive_casts');

        $modelTestState = new ModelTestState(TestModelWithPrimitiveCasts::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithNativeJsonCasts(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_native_json_casts/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_native_json_casts/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_native_json_casts');

        $modelTestState = new ModelTestState(TestModelWithNativeJsonCasts::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithCustomCast(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_custom_cast/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_custom_cast/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_custom_casts');

        $modelTestState = new ModelTestState(TestModelWithCustomCast::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithParameterizedCast(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_parameterized_cast/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_parameterized_cast/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_parameterized_casts');

        $modelTestState = new ModelTestState(TestModelWithParameterizedCast::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithCastable(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_castable/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_castable/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_castables');

        $modelTestState = new ModelTestState(TestModelWithCastable::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithCrossAttributeCast(): void
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_cross_attribute_cast/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_with_cross_attribute_cast/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_with_cross_attribute_casts');

        $modelTestState = new ModelTestState(TestModelWithCrossAttributeCast::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertNoChanges(): void
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));

        $this->mockGettingDatasetForChanges($datasetMock, $datasetMock, 'test_models');

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertNotChanged();
    }
}
