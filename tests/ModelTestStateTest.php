<?php

namespace RonasIT\Support\Tests;

use ReflectionClass;
use RonasIT\Support\Tests\Support\Mock\TestModel;
use RonasIT\Support\Tests\Support\Mock\TestModelWithoutJsonFields;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class ModelTestStateTest extends HelpersTestCase
{
    use TableTestStateMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;

        putenv('FAIL_EXPORT_JSON=false');
    }

    public function testInitialization()
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $originRecords = collect($this->getJsonFixture('initialization/origin_records.json'));

        $this->mockGettingDataset($datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $reflectionClass = new ReflectionClass($modelTestState);

        $jsonFields = $this->getProtectedProperty($reflectionClass, 'jsonFields', $modelTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $modelTestState);

        $this->assertEquals(['json_field', 'castable_field'], $jsonFields);
        $this->assertEquals($originRecords, $state);
    }

    public function testInitializationViaPrepareModelTestState()
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $mock = $this
            ->getMockBuilder(TestCase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $reflection = new ReflectionClass($mock);

        $reflection->getMethod('setGlobalExportMode')->invoke($mock);

        $prepareModelTestState = $reflection->getMethod('prepareModelTestState')->invoke($mock, TestModel::class);

        $testCaseGlobalExportMode = $reflection->getProperty('globalExportMode')->getValue($mock);

        $this->assertTrue($testCaseGlobalExportMode);
        $this->assertEquals($prepareModelTestState->globalExportMode, $testCaseGlobalExportMode);
    }

    public function testAssertChangesEqualsFixture()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_models');

        $modelTestState = new ModelTestState(TestModel::class);
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

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock, 'test_model_without_json_fields');

        $modelTestState = new ModelTestState(TestModelWithoutJsonFields::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture_without_json_fields.json');
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));

        $this->mockGettingDatasetForChanges($datasetMock, $datasetMock, 'test_models');

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertNotChanged();
    }
}
