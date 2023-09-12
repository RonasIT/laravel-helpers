<?php

namespace RonasIT\Support\Tests;

use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Database\Connection;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use RonasIT\Support\Exceptions\UnexpectedExportException;
use RonasIT\Support\Services\HttpRequestService;
use RonasIT\Support\Tests\Support\Traits\MockTrait;

class FixturesTraitTest extends HelpersTestCase
{
    use MockTrait;

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

    public function testGetFixtureNotExistsWithoutException()
    {
        $response = $this->getFixture('get_fixture/not_exists_fixture.json', false);

        $this->assertEquals('', $response);
    }

    public function testGetFixtureNotExistsWithException()
    {
        $this->expectException(AssertionFailedError::class);

        $this->getFixture('get_fixture/not_exists_fixture.json');

        $this->expectExceptionMessage('not_exists_fixture.json fixture does not exist');
    }

    public function testGetClearPsqlDatabaseQuery()
    {
        $tables = $this->getJsonFixture('clear_database/tables.json');

        $result = $this->getClearPsqlDatabaseQuery($tables);

        $this->assertEquals($this->getFixture('clear_database/clear_psql_db_query.sql'), $result);
    }

    public function testGetClearMysqlDatabaseQuery()
    {
        $tables = $this->getJsonFixture('clear_database/tables.json');

        $result = $this->getClearMySQLDatabaseQuery($tables);

        $this->assertEquals($this->getFixture('clear_database/clear_mysql_db_query.sql'), $result);
    }

    public function testPrepareSequences()
    {
        $connection = $this->mockClass(Connection::class, [], true);
        $mock = $this->mockClass(PostgreSQLSchemaManager::class, ['listTableNames'], true);

        $this->app->instance('db.connection', $connection);

        $mock->expects($this->once())
            ->method('listTableNames')
            ->willReturn($this->getJsonFixture('prepare_sequences/tables.json'));

        $connection->expects($this->once())
            ->method('getDoctrineSchemaManager')
            ->willReturn($mock);

        $connection->expects($this->once())
            ->method('unprepared')
            ->with($this->getFixture('prepare_sequences/sequences.sql'))
            ->willReturn(true);

        $this->prepareSequences($this->getTables());
    }
}
