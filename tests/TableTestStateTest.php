<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RonasIT\Support\Testing\TableTestState;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class TableTestStateTest extends TestCase
{
    use TableTestStateMockTrait;

    public function testInitialization()
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $originRecords = collect($this->getJsonFixture('initialization/origin_records.json'));

        $this->mockGettingDataset($datasetMock);

        $tableTestState = new TableTestState('test_models', ['json_field', 'castable_field']);
        $reflectionClass = new ReflectionClass($tableTestState);

        $jsonFields = $this->getProtectedProperty($reflectionClass, 'jsonFields', $tableTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $tableTestState);

        $this->assertEquals(['json_field', 'castable_field'], $jsonFields);
        $this->assertEquals($originRecords, $state);
    }

    public static function getInitializationViaPrepareTableTestStateFilters(): array
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

    #[DataProvider('getInitializationViaPrepareTableTestStateFilters')]
    public function testInitializationViaPrepareTableTestState(bool $testCaseGlobalExportMode)
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $actualGlobalExportModeValue = $this->mockTestStateCreationSetGlobalExportMode('prepareTableTestState', 'test_models', $testCaseGlobalExportMode);

        $this->assertEquals($actualGlobalExportModeValue, $testCaseGlobalExportMode);
    }

    public function testAssertChangesEqualsFixture()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_models');

        $modelTestState = new TableTestState('test_models', ['json_field', 'castable_field']);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithoutJsonFields()
    {
        $initialDatasetMock = collect(
            $this->getJsonFixture('changes_equals_fixture_without_json_fields/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            $this->getJsonFixture('changes_equals_fixture_without_json_fields/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_models');

        $modelTestState = new TableTestState('test_models');
        $modelTestState->assertChangesEqualsFixture('assertion_fixture_without_json_fields.json');
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));

        $this->mockGettingDatasetForChanges($datasetMock, $datasetMock, 'test_models');

        $modelTestState = new TableTestState('test_models', ['json_field', 'castable_field']);
        $modelTestState->assertNotChanged();
    }
}
