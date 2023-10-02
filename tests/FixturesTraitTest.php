<?php

namespace RonasIT\Support\Tests;

use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Illuminate\Database\Connection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use RonasIT\Support\Exceptions\ForbiddenExportModeException;
use RonasIT\Support\Tests\Support\Traits\MockTrait;

class FixturesTraitTest extends HelpersTestCase
{
    use MockTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;
    }

    public function getFixtureData(): array
    {
        return [
            [
                'input' => 'get_fixture/exists_fixture.json'
            ],
        ];
    }

    /**
     * @dataProvider getFixtureData
     *
     * @param string $input
     */
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
            'value' => 1234567890
        ];

        $this->exportJson('export_json/response.json', new TestResponse(
            new Response(json_encode($result))
        ));

        $this->assertEquals($this->getJsonFixture('export_json/response.json'), $result);

        $this->assertFileExists($this->getFixturePath('export_json/response.json'));
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
        $connection = $this->mockClass(Connection::class, [], true);

        $this->app->instance('db.connection', $connection);
        $this->dumpFileName = 'clear_database/empty_dump.sql';

        $connection
            ->expects($this->never())
            ->method('unprepared');

        $this->loadTestDump();
    }

    public function testLoadTestDumpForMysql()
    {
        $connection = $this->mockClass(Connection::class, [], true);

        $this->app->instance('db.connection', $connection);
        $this->dumpFileName = 'clear_database/dump.sql';

        Config::set('database.default', 'mysql');

        self::$tables = $this->getJsonFixture('clear_database/tables.json');

        $connection
            ->expects($this->exactly(2))
            ->method('unprepared')
            ->withConsecutive(
                [$this->getFixture('clear_database/clear_mysql_db_query.sql')],
                [$this->getFixture('clear_database/dump.sql')],
            );

        $this->loadTestDump();
    }

    public function testLoadTestDumpForPgsql()
    {
        $connection = $this->mockClass(Connection::class, [], true);

        $this->app->instance('db.connection', $connection);
        $this->dumpFileName = 'clear_database/dump.sql';

        Config::set('database.default', 'pgsql');

        self::$tables = $this->getJsonFixture('clear_database/tables.json');

        $connection
            ->expects($this->exactly(2))
            ->method('unprepared')
            ->withConsecutive(
                [$this->getFixture('clear_database/clear_pgsql_db_query.sql')],
                [$this->getFixture('clear_database/dump.sql')],
            );

        $this->loadTestDump();
    }

    public function testPrepareSequences()
    {
        $connection = $this->mockClass(Connection::class, [], true);
        $mock = $this->mockClass(PostgreSQLSchemaManager::class, ['listTableNames'], true);

        $this->app->instance('db.connection', $connection);

        $mock
            ->expects($this->once())
            ->method('listTableNames')
            ->willReturn($this->getJsonFixture('prepare_sequences/tables.json'));

        $connection
            ->expects($this->once())
            ->method('getDoctrineSchemaManager')
            ->willReturn($mock);

        $connection
            ->expects($this->once())
            ->method('unprepared')
            ->with($this->getFixture('prepare_sequences/sequences.sql'))
            ->willReturn(true);

        $this->prepareSequences($this->getTables());
    }
}
