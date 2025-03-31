<?php

namespace RonasIT\Support\Tests;

use Exception;
use Illuminate\Support\Facades\Config;
use RonasIT\Support\Tests\Support\Mock\Migrations\TestMigration;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class MigrationTraitTest extends TestCase
{
    use SqlMockTrait;

    protected TestMigration $migration;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = null;

        $this->migration = new TestMigration();

        Config::set('database.default', 'pgsql');
    }

    public function testChangeEnum()
    {
        $this->mockStatementDBFacade('ALTER TABLE some_table DROP CONSTRAINT some_table_enum_field_check');

        $this->mockStatementDBFacade(
            'ALTER TABLE some_table ADD CONSTRAINT some_table_enum_field_check CHECK (enum_field::text = ANY (' .
            "ARRAY['first_value'::character varying, 'second_value'::character varying]::text[]" .
            '))'
        );

        Config::set('database.default', 'pgsql');

        $this
            ->migration
            ->changeEnum('some_table', 'enum_field', [
                'first_value',
                'second_value',
            ]);
    }

    public function testChangeEnumRenameField()
    {
        $this->mockStatementDBFacade('ALTER TABLE some_table DROP CONSTRAINT some_table_enum_field_check');

        $this->mockUpdateDBFacade(
            table: 'some_table',
            where: ['enum_field' => 'first_value'],
            update: ['enum_field' => 'renamed_first_value'],
        );

        $this->mockUpdateDBFacade(
            table: 'some_table',
            where: ['enum_field' => 'third_value'],
            update: ['enum_field' => 'renamed_third_value'],
        );

        $this->mockStatementDBFacade(
            'ALTER TABLE some_table ADD CONSTRAINT some_table_enum_field_check CHECK (enum_field::text = ANY (' .
                "ARRAY['renamed_first_value'::character varying, 'second_value'::character varying, 'renamed_third_value'::character varying]::text[]" .
            '))'
        );

        $this
            ->migration
            ->changeEnum('some_table', 'enum_field', [
                'first_value',
                'second_value',
                'third_value',
            ], [
                'first_value' => 'renamed_first_value',
                'third_value' => 'renamed_third_value',
            ]);
    }

    public function testChangeEnumDriverNotAvailable()
    {
        Config::set('database.default', 'testing');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database driver "testing" not available');

        $this->migration->changeEnum('some_table', 'enum_field', [
            'first_value',
            'second_value',
        ]);
    }
}