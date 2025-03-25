<?php

namespace RonasIT\Support\Tests;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PDOStatement;
use RonasIT\Support\Tests\Support\Mock\Migration\Migration;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class MigrationTraitTest extends TestCase
{
    use SqlMockTrait;

    protected Migration $migration;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;

        $this->migration = new Migration();
    }

    public function testChangeEnum()
    {
        $this->mockStatementDBFacade([
            'ALTER TABLE some_table DROP CONSTRAINT some_table_enum_field_check',

            'ALTER TABLE some_table '.
            'ADD CONSTRAINT some_table_enum_field_check '.
            'CHECK (enum_field::text = ANY '.
            "(ARRAY['first_value'::character varying, 'second_value'::character varying]::text[]))",
        ]);

        Config::set('database.default', 'pgsql');

        $this->migration->changeEnum('some_table', 'enum_field', [
            'first_value',
            'second_value',
        ]);
    }

    public function testChangeEnumDriverNotAvailable()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database driver "testing" not available');

        $this->migration->changeEnum('some_table', 'enum_field', [
            'first_value',
            'second_value',
        ]);
    }
}