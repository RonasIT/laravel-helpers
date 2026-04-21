<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use RonasIT\Support\Testing\ModelTestState;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelNonIdPrimaryKey;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithoutJsonFields;
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

    public function testInitialization()
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));
        $originRecords = collect($this->getJsonFixture('initialization/origin_records.json'));

        $this->mockGettingDataset($datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $reflectionClass = new ReflectionClass($modelTestState);

        $jsonFields = $this->getProtectedProperty($reflectionClass, 'jsonFields', $modelTestState);
        $state = $this->getProtectedProperty($reflectionClass, 'state', $modelTestState);

        $this->assertEquals(['json_field', 'castable_field', 'cast_binary_field'], $jsonFields);
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
    public function testInitializationViaPrepareTableTestState(bool $testCaseGlobalExportMode)
    {
        $datasetMock = collect($this->getJsonFixture('initialization/dataset.json'));

        $this->mockGettingDataset($datasetMock);

        $actualGlobalExportModeValue = $this->mockTestStateCreationSetGlobalExportMode('prepareModelTestState', TestModel::class, $testCaseGlobalExportMode);

        $this->assertEquals($actualGlobalExportModeValue, $testCaseGlobalExportMode);
    }

    public function testAssertChangesEqualsFixture()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/initial_dataset.json'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture/changed_dataset.json'));

        $this->mockGettingDatasetForChanges($changedDatasetMock, $initialDatasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture.json');
    }

    public function testAssertChangesWithoutJsonFields()
    {
        $initialDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_without_json_fields/initial_dataset.json'),
        );
        $changedDatasetMock = collect(
            value: $this->getJsonFixture('changes_equals_fixture_without_json_fields/changed_dataset.json'),
        );

        $this->mockGettingDatasetForChanges(
            responseMock: $changedDatasetMock,
            initialState: $initialDatasetMock,
            tableName: 'test_model_without_json_fields',
        );

        $modelTestState = new ModelTestState(TestModelWithoutJsonFields::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture_without_json_fields.json');
    }

    public function testAssertChangesBinaryStringMysqlDriver()
    {
        $initialDatasetMock = collect([[
            'id' => 1,
            'cast_binary_field' => null,
        ]]);

        $changedDatasetMock = collect([[
            'id' => 1,
            'cast_binary_field' => md5('some_string', true),
        ]]);

        $this->mockGettingDatasetForChanges(
            responseMock: $changedDatasetMock,
            initialState: $initialDatasetMock,
            binaryColumn: 'cast_binary_field',
            dbDriver: 'mysql',
        );

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertChangesEqualsFixture('null_to_binary_string_changes');
    }

    public function testAssertChangesBinaryToNull()
    {
        $initialDatasetMock = collect([[
            'id' => 1,
            'cast_binary_field' => md5('some_string', true),
        ]]);

        $changedDatasetMock = collect([[
            'id' => 1,
            'cast_binary_field' => null,
        ]]);

        $this->mockGettingDatasetForChanges(
            responseMock: $changedDatasetMock,
            initialState: $initialDatasetMock,
            binaryColumn: 'cast_binary_field',
        );

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertChangesEqualsFixture('binary_string_to_null_changes');
    }

    public function testAssertChangesBinary()
    {
        $initialDatasetMock = collect([[
            'id' => 1,
            'binary_field' => null,
        ]]);

        $resource = fopen('php://memory', 'r+b');
        fwrite($resource, md5('some_string', true));
        rewind($resource);

        $changedDatasetMock = collect([[
            'id' => 1,
            'binary_field' => $resource,
        ]]);

        $this->mockGettingDatasetForChanges(
            responseMock: $changedDatasetMock,
            initialState: $initialDatasetMock,
            binaryColumn: 'binary_field',
        );

        $modelTestState = new ModelTestState(TestModel::class);

        $modelTestState->assertChangesEqualsFixture('null_to_binary_resource_changes');

        fclose($resource);
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));

        $this->mockGettingDatasetForChanges($datasetMock, $datasetMock);

        $modelTestState = new ModelTestState(TestModel::class);
        $modelTestState->assertNotChanged();
    }

    public function testAssertChangesWithCustomPrimaryKey()
    {
        $initialDatasetMock = collect($this->getJsonFixture('changes_equals_fixture_primary_key/initial_dataset'));
        $changedDatasetMock = collect($this->getJsonFixture('changes_equals_fixture_primary_key/changed_dataset'));

        $this->mockGettingDatasetForChanges(
            responseMock: $changedDatasetMock,
            initialState: $initialDatasetMock,
            tableName: 'test_model_non_id_primary_keys',
            uniqueKey: 'name',
        );

        $modelTestState = new ModelTestState(TestModelNonIdPrimaryKey::class);
        $modelTestState->assertChangesEqualsFixture('assertion_fixture_primary_key');
    }
}
