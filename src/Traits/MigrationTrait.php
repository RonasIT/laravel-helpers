<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;

trait MigrationTrait
{
    private function changeEnum(string $table, string $field, array $values): void
    {
        $databaseDriver = config('database.default');

        match ($databaseDriver) {
            'pgsql' => $this->changePostgresEnums($table, $field, $values),
            default => throw new Exception("Database driver \"{$databaseDriver}\" not available")
        };
    }

    private function changePostgresEnums(string $table, string $field, array $values): void
    {
        $check = "{$table}_{$field}_check";

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$check}");

        $values = $this->preparePostgreValues($values);

        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$check} CHECK ({$field}::text = ANY (ARRAY[{$values}]::text[]))");
    }

    private function preparePostgreValues(array $values): string
    {
        $values = array_map(fn ($value) => "'{$value}'::character varying", $values);

        return join(', ', $values);
    }

    protected function alterColumn(string $table, string $columnName, string $command): void
    {
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$columnName} {$command}");
    }

    protected function changeToUUID(string $table, string $column): void
    {
        $this->alterColumn($table, $column, "TYPE uuid USING {$column}::uuid");
    }

    protected function renameTable(string $from, string $to): void
    {
        Schema::rename($from, $to);

        DB::statement("ALTER SEQUENCE {$from}_id_seq RENAME TO {$to}_id_seq");
    }

    protected function isIndexExists(Blueprint $table, string $indexName): bool
    {
        return Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->introspectTable($table->getTable())
            ->hasIndex($indexName);
    }

    public function addForeignKey($fromEntity, $toEntity, $needAddField = false, $onDelete = 'cascade')
    {
        Schema::table(
            $this->getTableName($fromEntity),
            function (Blueprint $table) use ($toEntity, $needAddField, $onDelete) {
                $fieldName = Str::snake($toEntity) . '_id';

                if ($needAddField) {
                    $table->unsignedInteger($fieldName);
                }

                $table
                    ->foreign($fieldName)
                    ->references('id')
                    ->on($this->getTableName($toEntity))
                    ->onDelete($onDelete);
            }
        );
    }

    public function dropForeignKey($fromEntity, $toEntity, $needDropField = false)
    {
        $field = Str::snake($toEntity) . '_id';
        $table = $this->getTableName($fromEntity);

        if (Schema::hasColumn($table, $field)) {
            Schema::table($table, function (Blueprint $table) use ($field, $needDropField) {
                $table->dropForeign([$field]);

                if ($needDropField) {
                    $table->dropColumn([$field]);
                }
            });
        }
    }

    public function createBridgeTable($fromEntity, $toEntity)
    {
        $bridgeTableName = $this->getBridgeTable($fromEntity, $toEntity);

        Schema::create($bridgeTableName, function (Blueprint $table) {
            $table->increments('id');
        });

        $this->addForeignKey($bridgeTableName, $fromEntity, true);
        $this->addForeignKey($bridgeTableName, $toEntity, true);
    }

    public function dropBridgeTable($fromEntity, $toEntity)
    {
        $bridgeTableName = $this->getBridgeTable($fromEntity, $toEntity);

        $this->dropForeignKey($bridgeTableName, $fromEntity, true);
        $this->dropForeignKey($bridgeTableName, $toEntity, true);

        Schema::drop($bridgeTableName);
    }

    protected function getBridgeTable($fromEntity, $toEntity)
    {
        $entities = [Str::snake($fromEntity), Str::snake($toEntity)];
        sort($entities, SORT_STRING);

        return implode('_', $entities);
    }

    protected function getTableName($entityName)
    {
        if (Schema::hasTable($entityName)) {
            return $entityName;
        }

        $entityName = Str::snake($entityName);

        return Str::plural($entityName);
    }
}
