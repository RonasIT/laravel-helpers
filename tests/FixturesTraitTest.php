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
use RonasIT\Support\Exceptions\ForbiddenExportModeException;
use RonasIT\Support\Traits\MockTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FixturesTraitTest extends HelpersTestCase
{
    use MockTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;
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
            new Response(json_encode($result))
        ));

        $this->assertEquals($this->getJsonFixture('export_json/response.json'), $result);

        $this->assertFileExists($this->getFixturePath('export_json/response.json'));
    }

    public function testExportJsonDirNotExists()
    {
        putenv('FAIL_EXPORT_JSON=false');

        $result = [
            'value' => 1234567890,
        ];

        $fixtureName = 'export_json/some_directory/response.json';

        $this->exportJson($fixtureName, new TestResponse(
            new Response(json_encode($result))
        ));

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
            new BinaryFileResponse(
                Storage::disk('files')->path('content_source.txt')
            ),
        );

        $this->exportFile($response, 'export_file/content_result.txt');

        $this->assertEquals(
            expected: $this->getJsonFixture('export_file/result.txt'),
            actual: $this->getJsonFixture('export_file/content_result.txt'),
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

    public function testLoadTestDumpForMysql()
    {
        $connection = $this->mockClass(MysqlConnection::class, [
            $this->functionCall('unprepared', [$this->getFixture('clear_database/clear_mysql_db_query.sql')]),
            $this->functionCall('unprepared', [$this->getFixture('clear_database/dump.sql')]),
        ], true);

        $db = $this->mockClass(DatabaseManager::class, [
            $this->functionCall('connection', [null], $connection),
            $this->functionCall('connection', [null], $connection),
        ], true);

        $this->app->instance('db', $db);
        $this->dumpFileName = 'clear_database/dump.sql';

        Config::set('database.default', 'mysql');

        self::$tables = $this->getJsonFixture('clear_database/tables.json');

        $this->loadTestDump();
    }

    public function testLoadTestDumpForPgsql()
    {
        $connection = $this->mockClass(PostgresConnection::class, [
            $this->functionCall('unprepared', [$this->getFixture('clear_database/clear_pgsql_db_query.sql')]),
            $this->functionCall('unprepared', [$this->getFixture('clear_database/dump.sql')]),
        ], true);

        $db = $this->mockClass(DatabaseManager::class, [
            $this->functionCall('connection', [null], $connection),
            $this->functionCall('connection', [null], $connection),
        ], true);

        $this->app->instance('db', $db);
        $this->dumpFileName = 'clear_database/dump.sql';

        Config::set('database.default', 'pgsql');

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

        $db = $this->mockClass(DatabaseManager::class, [
            $this->functionCall('connection', [null], $connection),
        ], true);

        $this->app->instance('db', $db);

        Config::set('database.default', 'mysql');

        $this->getTables();

        $this->assertEqualsFixture('get_tables/tables.json', self::$tables);
    }

    public function testPrepareSequences()
    {
        $sequences = collect($this->getJsonFixture('prepare_sequences/information_schema.json'))
            ->map(fn ($item) => (object) $item);

        $connection = $this->mockClass(PostgresConnection::class, [
            $this->functionCall('getQueryGrammar', [], new Grammar()),
            $this->functionCall('getPostProcessor', [], new Processor()),
            $this->functionCall('select', [], $sequences),
            $this->functionCall('unprepared', [$this->getFixture('prepare_sequences/sequences.sql')]),
        ], true);

        $db = $this->mockClass(DatabaseManager::class, [
            $this->functionCall('connection', [null], $connection),
            $this->functionCall('connection', [null], $connection),
        ], true);

        $this->app->instance('db', $db);

        Config::set('database.default', 'pgsql');

        $this->prepareSequences(['roles']);
    }

    public function testGetFixtureWithoutGlobalExportMode()
    {
        $content = $this->getJsonFixture('get_fixture/export_fixture.json');

        unset($this->globalExportMode);

        $this->assertEqualsFixture('get_fixture/export_fixture.json', $content);
    }
}
