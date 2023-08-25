<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\AssertionFailedError;

class FixturesTraitTest extends HelpersTestCase
{
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
        $this->expectExceptionMessage('not_exists_fixture.json fixture does not exist');

        $this->getFixture('get_fixture/not_exists_fixture.json');
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
}
