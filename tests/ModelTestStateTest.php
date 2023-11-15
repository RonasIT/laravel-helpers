<?php

namespace RonasIT\Support\Tests;

use ReflectionClass;
use RonasIT\Support\Tests\Support\Mock\TestModel;
use RonasIT\Support\Tests\Support\Traits\ModelTestStateMockTrait;
use RonasIT\Support\Tests\Support\Traits\MockTrait;

class ModelTestStateTest extends HelpersTestCase
{
    use ModelTestStateMockTrait;
    use MockTrait;

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
        $jsonFieldsProperty = $reflectionClass->getProperty('jsonFields');
        $jsonFieldsProperty->setAccessible(true);

        $jsonFields = $jsonFieldsProperty->getValue($modelTestState);

        $this->assertNotEmpty($jsonFields);
        $this->assertEquals(['json_field', 'castable_field'], $jsonFields);
        $this->assertEquals($originRecords, $modelTestState->getState());
    }

    public function testAssertChangesEqualsFixture()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));
        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock);

        $modelTestState = new ModelTestState(TestModel::class);

        $this->assertEqualsFixture(
            $modelTestState->getFixturePath('changes_equals_fixture/assertion_fixture.json'),
            $modelTestState->getChanges()
        );
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $this->assertEquals($modelTestState->getExpectedEmptyState(), $modelTestState->getChanges());
    }
}
