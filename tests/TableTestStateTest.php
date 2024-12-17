<?php

namespace RonasIT\Support\Tests;

use ReflectionClass;
use RonasIT\Support\Tests\Support\Traits\TableTestStateMockTrait;

class TableTestStateTest extends HelpersTestCase
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

    public function testInitializationViaPrepareTableTestState()
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $mock = $this->getMockBuilder(TestCase::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $reflection = new ReflectionClass($mock);

        $this->assertFalse($reflection->getProperty('globalExportMode')->getValue($mock));

        $setGlobalExportMode = $reflection->getMethod('setGlobalExportMode');
        $setGlobalExportMode->invoke($mock);

        $prepareTableTestState = $reflection->getMethod('prepareTableTestState');
        $prepareTableTestState->invoke($mock, 'test_models');

        $this->assertTrue($reflection->getProperty('globalExportMode')->getValue($mock));
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
