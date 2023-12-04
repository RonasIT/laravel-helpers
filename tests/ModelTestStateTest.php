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

        $jsonFields = $this->getProtectedProperty($reflectionClass, 'jsonFields', $modelTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $modelTestState);

        $this->assertNotEmpty($jsonFields);
        $this->assertEquals(['json_field', 'castable_field'], $jsonFields);
        $this->assertEquals($originRecords, $state);
    }

    public function testAssertChangesEqualsFixture()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));
        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));
        $this->mockGettingDataset($datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertNotChanged();
    }

    protected function getProtectedProperty(ReflectionClass $reflectionClass, string $methodName, $objectInstance)
    {
        $property = $reflectionClass->getProperty($methodName);
        $property->setAccessible(true);

        return $property->getValue($objectInstance);
    }
}
