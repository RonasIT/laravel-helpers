<?php

namespace RonasIT\Support\Tests;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Mockery;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Traits\FixturesTrait;
use RonasIT\Support\Traits\NovaTestTrait;

class NovaTestTraitTest extends HelpersTestCase
{
    use FixturesTrait, MockTrait, NovaTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = [];

        putenv('FAIL_EXPORT_JSON=false');
    }

    public function testActingAsNovaUser()
    {
        $userId = 1;
        $this->novaActingAs($userId);
        $session = $this->app['session']->getDrivers()['array'];

        $loginSession = array_filter($session->all(), function ($key) {
            return str_starts_with($key, 'login_session_');
        }, ARRAY_FILTER_USE_KEY);

        $this->assertNotEmpty($loginSession);
        $this->assertEquals('laravel_session', $session->getName());
        $this->assertTrue(array_values($loginSession)[0] === $userId);
    }

    public function testCacheJsonFields()
    {
        Config::set('database.default', 'mysql');

        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock->shouldReceive('getColumnListing')
            ->andReturn(['id', 'json_column'])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'json_column')
            ->andReturn('json');

        $this->cacheJsonFields('users');
        $this->assertNotEmpty(self::$jsonFields);
        $this->assertEquals(['json_column'], self::$jsonFields['users']);

        self::$jsonFields = [];
    }

    public function tesCachetWithoutJsonFields()
    {
        Config::set('database.default', 'mysql');

        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock->shouldReceive('getColumnListing')
            ->andReturn(['id', 'name'])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'name')
            ->andReturn('string');

        $this->cacheJsonFields('users');
        $this->assertEmpty(self::$jsonFields['users']);
    }

    public function testPrepareChangesWithJsonFields()
    {
        self::$jsonFields['users'][] = 'json_column';

        $unpreparedChanges = $this->getJsonFixture('prepare_changes_with_json/unprepared_changes_with_json_field.json');
        $result = $this->prepareChanges('users', $unpreparedChanges);
        $this->assertEqualsFixture('prepare_changes_with_json/prepared_changes_with_json_field_result.json', $result);
    }

    public function testPrepareChangesWithoutJsonFields()
    {
        self::$jsonFields['users'] = [];

        $unpreparedChanges = $this->getJsonFixture('prepare_changes_without_json/unprepared_changes_without_json_field.json');
        $result = $this->prepareChanges('users', $unpreparedChanges);
        $this->assertEqualsFixture('prepare_changes_without_json/prepared_changes_without_json_field_result.json', $result);
    }

    public function testAssertChangesEqualsFixture()
    {
        $datasetMock = collect($this->getJsonFixture('changes_equals_fixture/dataset.json'));
        $originRecords = collect($this->getJsonFixture('changes_equals_fixture/origin_records.json'));

        $this->mockNovaTrait($datasetMock);

        $this->assertChangesEqualsFixture('users', 'changes_equals_fixture/assertion_fixture.json', $originRecords);
    }

    public function testAssertNoChanges()
    {
        $datasetMock = collect($this->getJsonFixture('get_without_changes/dataset.json'));
        $originRecords = collect($this->getJsonFixture('get_without_changes/origin_records.json'));

        $this->mockNovaTrait($datasetMock);

        $this->assertNoChanges('users', $originRecords);
    }

    protected function mockNovaTrait(Collection $datasetMock)
    {
        $this->mockGettingDataset($datasetMock);
        $this->mockGettingColumnTypes();
    }

    protected function mockGettingDataset(Collection $datasetMock)
    {
        $builderMock = Mockery::mock(Builder::class);

        $builderMock->shouldReceive('orderBy')
            ->andReturn($builderMock)
            ->shouldReceive('get')
            ->andReturn($datasetMock);

        $dbMock = Mockery::mock('alias:' . DB::class);
        $dbMock->shouldReceive('table')
            ->andReturn($builderMock);
    }

    protected function mockGettingColumnTypes()
    {
        Config::set('database.default', 'mysql');

        $schemeMock = Mockery::mock('overload:' . MysqlBuilder::class);
        $schemeMock->shouldReceive('getColumnListing')
            ->andReturn([
                'id', 'user_id', 'title', 'text', 'description',
                'is_public', 'json_column', 'created_at', 'updated_at'
            ])

            ->shouldReceive('getColumnType')
            ->with('users', 'id')
            ->andReturn('int')

            ->shouldReceive('getColumnType')
            ->with('users', 'title')
            ->andReturn('string')

            ->shouldReceive('getColumnType')
            ->with('users', 'text')
            ->andReturn('text')

            ->shouldReceive('getColumnType')
            ->with('users', 'description')
            ->andReturn('string')

            ->shouldReceive('getColumnType')
            ->with('users', 'is_public')
            ->andReturn('boolean')

            ->shouldReceive('getColumnType')
            ->with('users', 'created_at')
            ->andReturn('datetime')

            ->shouldReceive('getColumnType')
            ->with('users', 'updated_at')
            ->andReturn('datetime')

            ->shouldReceive('getColumnType')
            ->with('users', 'json_column')
            ->andReturn('json');
    }
}
