<?php

namespace RonasIT\Support\Tests;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use RonasIT\Support\Exceptions\ForbiddenExportModeException;
use RonasIT\Support\Tests\Support\Traits\FixturesTestTrait;
use RonasIT\Support\Traits\MockTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FixturesTraitTest extends TestCase
{
    use FixturesTestTrait;
    use MockTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;
        self::$sequences = [];
    }

    public static function getFixtureData(): array
    {
        return [
            [
                'input' => 'get_fixture/exists_fixture.json',
            ],
        ];
    }

    #[DataProvider('getFixtureData')]
    public function testGetFixture(string $input)
    {
        $response = $this->getJsonFixture($input);

        $this->assertEqualsFixture($input, $response);
    }

    public function testGetFixtureWithSave()
    {
        $response = $this->getJsonFixture('get_fixture/exists_fixture.json');

        $this->expectException(ForbiddenExportModeException::class);

        $this->assertEqualsFixture('get_fixture/exists_fixture.json', $response, true);
    }

    public function testExportJson()
    {
        putenv('FAIL_EXPORT_JSON=false');

        $result = [
            'value' => 1234567890,
        ];

        $this->exportJson('export_json/response.json', new TestResponse(
            response: new Response(json_encode($result)),
        ));

        $this->assertEquals($this->getJsonFixture('export_json/response.json'), $result);

        $this->assertFileExists($this->getFixturePath('export_json/response.json'));
    }

    public function testGetJsonFixtureWithoutExtension()
    {
        $response = $this->getJsonFixture('get_fixture/exists_fixture');

        $this->assertEqualsFixture('get_fixture/exists_fixture.json', $response);
    }

    public function testExportJsonWithoutExtension()
    {
        putenv('FAIL_EXPORT_JSON=false');

        $fixturePath = $this->getFixturePath('export_json/response.json');

        if (file_exists($fixturePath)) {
            unlink($fixturePath);
        }

        $result = [
            'value' => 1234567890,
        ];

        $this->exportJson('export_json/response', new TestResponse(
            response: new Response(json_encode($result)),
        ));

        $this->assertFileExists($this->getFixturePath('export_json/response.json'));
    }

    public function testExportJsonDirNotExists()
    {
        putenv('FAIL_EXPORT_JSON=false');

        $result = [
            'value' => 1234567890,
        ];

        $fixtureName = 'export_json/some_directory/response.json';

        $this->exportContent(json_encode($result), $fixtureName);

        $this->assertEquals($this->getJsonFixture($fixtureName), $result);

        $fixturePath = $this->getFixturePath($fixtureName);

        $this->assertFileExists($fixturePath);

        unlink($fixturePath);

        rmdir(Str::beforeLast($fixturePath, '/'));
    }

    public function testExportFile()
    {
        putenv('FAIL_EXPORT_JSON=false');

        Storage::fake('files');
        Storage::disk('files')->put('content_source.txt', 'some content is here');

        $response = new TestResponse(
            response: new BinaryFileResponse(
                file: Storage::disk('files')->path('content_source.txt'),
            ),
        );

        $this->exportFile($response, 'export_file/content_result.txt');

        $this->assertEquals(
            expected: $this->getFixture('export_file/result.txt'),
            actual: $this->getFixture('export_file/content_result.txt'),
        );
    }

    public function testGetFixtureNotExistsWithoutException()
    {
        $response = $this->getFixture('get_fixture/not_exists_fixture.json', false);

        $this->assertEquals('', $response);
    }

    public function testGetFixtureNotExistsWithException()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('not_exists_fixture.json fixture does not exist');

        $this->getFixture('get_fixture/not_exists_fixture.json');
    }

    public function testLoadEmptyTestDump()
    {
        $db = $this->mockNoCalls(DatabaseManager::class, null, true);

        $this->app->instance('db', $db);
        $this->dumpFileName = 'clear_database/empty_dump.sql';

        $this->loadTestDump();
    }

    public static function loadTestDumpData(): array
    {
        return [
            'mysql' => [
                'driver' => 'mysql',
                'connectionClass' => MySqlConnection::class,
                'clearSqlFixture' => 'clear_database/clear_mysql_db_query.sql',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'connectionClass' => PostgresConnection::class,
                'clearSqlFixture' => 'clear_database/clear_pgsql_db_query.sql',
            ],
        ];
    }

    #[DataProvider('loadTestDumpData')]
    public function testLoadTestDump(string $driver, string $connectionClass, string $clearSqlFixture)
    {
        $connection = $this->mockClass($connectionClass, [
            $this->functionCall('unprepared', [$this->getFixture($clearSqlFixture)]),
            $this->functionCall('unprepared', [$this->getFixture('clear_database/dump.sql')]),
        ], true);

        $this->bindMockedDbInstance($connection);
        $this->dumpFileName = 'clear_database/dump.sql';

        Config::set('database.default', $driver);

        self::$tables = $this->getJsonFixture('clear_database/tables.json');

        $this->loadTestDump();
    }

    public function testGetTables()
    {
        $mock = $this->mockClass(MySqlBuilder::class, [
            $this->functionCall('getTables', [], $this->getJsonFixture('get_tables/tables.json')),
        ], true);

        $connection = $this->mockClass(MySqlConnection::class, [
            $this->functionCall('getSchemaBuilder', [], $mock),
        ], true);

        $this->bindMockedDbInstance($connection, 1);

        Config::set('database.default', 'mysql');

        $this->getTables();

        $this->assertEqualsFixture('get_tables/tables.json', self::$tables);
    }

    public function testPrepareSequences()
    {
        $sequences = collect($this->getJsonFixture('prepare_sequences/information_schema.json'))
            ->map(fn ($item) => (object) $item);

        $connection = $this->mockClass(PostgresConnection::class, [
            $this->functionCall('getPostProcessor', [], new Processor()),
            $this->functionCall('select', [
                'select "table_name", "table_schema", "column_name", "column_default" from "information_schema"."columns" where "column_default" LIKE ?',
                ['nextval%'],
                true,
            ], $sequences),
            $this->functionCall('unprepared', [$this->getFixture('prepare_sequences/sequences.sql')]),
        ], true);

        $connection->setQueryGrammar(new Grammar($connection));

        $this->bindMockedDbInstance($connection);

        Config::set('database.default', 'pgsql');

        $this->prepareSequences(['roles']);
    }

    public function testPrepareSequencesAllTablesExcluded(): void
    {
        $sequences = collect($this->getJsonFixture('prepare_sequences_all_tables_excluded/information_schema.json'))
            ->map(fn (array $item) => (object) $item);

        $connection = $this->mockClass(
            class: PostgresConnection::class,
            callChain: [
                $this->functionCall('getPostProcessor', [], new Processor()),
                $this->functionCall(
                    name: 'select',
                    arguments: [
                        'select "table_name", "table_schema", "column_name", "column_default" from "information_schema"."columns" where "column_default" LIKE ?',
                        ['nextval%'],
                        true,
                    ],
                    result: $sequences,
                ),
            ],
            disableConstructor: true,
        );

        $connection->setQueryGrammar(new Grammar($connection));

        $db = $this->mockClass(
            class: DatabaseManager::class,
            callChain: [
                $this->functionCall('connection', [null], $connection),
            ],
            disableConstructor: true,
        );

        $this->app->instance('db', $db);

        Config::set('database.default', 'pgsql');

        $this->prepareSequences(['roles']);
    }

    public function testGetFixtureWithoutGlobalExportMode()
    {
        $content = $this->getJsonFixture('get_fixture/export_fixture.json');

        $this->globalExportMode = false;

        $this->assertEqualsFixture('get_fixture/export_fixture.json', $content);
    }

    public function testAssertEqualsFixtureNotEqualErrorMessage()
    {
        $fixturePath = $this->getFixturePath($fixtureName = 'get_fixture/export_fixture.json');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            "Failed asserting that the provided data equal to fixture: {$fixturePath}",
        );

        $this->assertEqualsFixture($fixtureName, ['content' => 'incorrect']);
    }

    public static function assertEqualsVersionedFixtureData(): array
    {
        return [
            'empty_versions_array' => [
                'fixture' => 'assert_versioned_fixture/response.json',
                'data' => ['default_response'],
                'versions' => [],
            ],
            'single_version_above_current' => [
                'fixture' => 'assert_versioned_fixture/response.json',
                'data' => ['response_before_v12'],
                'versions' => [12],
            ],
            'two_versions_picks_minimum' => [
                'fixture' => 'assert_versioned_fixture/response.json',
                'data' => ['response_before_v12'],
                'versions' => [13, 12],
            ],
            'fixture_in_subdirectory' => [
                'fixture' => 'assert_versioned_fixture/subdir/response.json',
                'data' => ['subdir_response_before_v12'],
                'versions' => [12],
            ],
        ];
    }

    #[DataProvider('assertEqualsVersionedFixtureData')]
    public function testAssertEqualsVersionedFixture(string $fixture, array $data, array $versions): void
    {
        $this->mockLaravelVersion('11.0.0');

        $this->assertEqualsVersionedFixture($fixture, $data, $versions);
    }

    public static function assertEqualsVersionedFixtureRangesData(): array
    {
        return [
            'current_v9_uses_before_v10' => ['9.0.0', ['response_before_v10']],
            'current_v10_uses_before_v12' => ['10.0.0', ['response_before_v12']],
            'current_v10_minor_uses_before_v12' => ['10.1.0', ['response_before_v12']],
            'current_v11_uses_before_v12' => ['11.0.0', ['response_before_v12']],
            'current_v12_uses_default' => ['12.0.0', ['default_response']],
            'current_v13_uses_default' => ['13.0.0', ['default_response']],
        ];
    }

    #[DataProvider('assertEqualsVersionedFixtureRangesData')]
    public function testAssertEqualsVersionedFixtureRanges(string $appVersion, array $data): void
    {
        $this->mockLaravelVersion($appVersion);

        $this->assertEqualsVersionedFixture('assert_versioned_fixture/response.json', $data, [10, 12]);
    }

    public function testAssertEqualsVersionedFixtureWithExportMode(): void
    {
        putenv('FAIL_EXPORT_JSON=false');

        $this->mockLaravelVersion('11.0.0');

        $this->assertEqualsVersionedFixture(
            fixture: 'assert_versioned_fixture/export_response.json',
            data: ['exported_response'],
            versions: [12],
            exportMode: true,
        );

        $exportedFixturePath = $this->getFixturePath('assert_versioned_fixture/laravel_before_v12/export_response.json');

        $this->assertFileExists($exportedFixturePath);

        unlink($exportedFixturePath);
    }

    public function testPrepareMySQLAutoIncrement()
    {
        $mock = $this->mockClass(MySqlBuilder::class, [
            $this->functionCall(
                name: 'getTables',
                result: $this->getJsonFixture('set_auto_increment/get_tables.json')),
        ], true);

        $connection = $this->mockClass(MySqlConnection::class, [
            $this->functionCall(
                name: 'getSchemaBuilder',
                result: $mock,
            ),
            $this->functionCall('unprepared', [$this->getFixture('set_auto_increment/set_auto_increment.sql')]),
        ], true);

        $this->bindMockedDbInstance($connection);

        Config::set('database.default', 'mysql');

        $this->resetMySQLAutoIncrement($this->getTables(), ['roles', 'groups']);
    }
}
