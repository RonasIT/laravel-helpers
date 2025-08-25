<?php

namespace RonasIT\Support\Traits;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\StringType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;

trait MigrationTrait
{
    public function __construct()
    {
        $this->registerEnumType();
    }

    public function changeEnum(string $table, string $field, array $values, array $valuesToRename = []): void
    {
        $databaseDriver = config('database.default');

        match ($databaseDriver) {
            'pgsql' => $this->changePostgresEnum($table, $field, $values, $valuesToRename),
            'mysql' => $this->changeMySqlEnum($table, $field, $values, $valuesToRename),
            default => throw new Exception("Database driver \"{$databaseDriver}\" not available"),
        };
    }

    private function changeMySqlEnum(string $table, string $field, array $values, array $valuesToRename = []): void
    {
        if (!empty($valuesToRename)) {
            $withRenamedValues = array_merge($values, array_keys($valuesToRename));

            $this->setMySqlEnum($table, $field, $withRenamedValues);

            $this->updateRenamedValues($table, $field, $valuesToRename);
        }

        $this->setMySqlEnum($table, $field, $values);
    }

    private function changePostgresEnum(string $table, string $field, array $values, array $valuesToRename = []): void
    {
        $check = "{$table}_{$field}_check";

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$check}");

        $this->updateRenamedValues($table, $field, $valuesToRename);

        $values = $this->preparePostgresValues($values);

        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$check} CHECK ({$field}::text = ANY (ARRAY[{$values}]::text[]))");
    }

    private function updateRenamedValues(string $table, string $field, array $valuesToRename): void
    {
        foreach ($valuesToRename as $key => $value) {
            DB::table($table)->where([$field => $key])->update([$field => $value]);
        }
    }

    private function setMySqlEnum(string $table, string $field, array $values): void
    {
        $values = Arr::map($values, fn ($value) => "'{$value}'");

        $enum = implode( ', ', $values);

        DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$field} ENUM({$enum})");
    }

    private function preparePostgresValues(array $values): string
    {
        $values = array_map(fn ($value) => "'{$value}'::character varying", $values);

        return join(', ', $values);
    }

    private function registerEnumType(): void
    {
        if (!Type::hasType('enum')) {
            Type::addType('enum', StringType::class);
        }
    }

    public function addForeignKey(string $fromEntity, string $toEntity, $needAddField = false, $onDelete = 'cascade'): void
    {
        Schema::table(
            table: $this->getTableName($fromEntity),
            callback: function (Blueprint $table) use ($toEntity, $needAddField, $onDelete) {
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

    public function dropForeignKey(string $fromEntity, string $toEntity, $needDropField = false): void
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

    public function createBridgeTable(string $fromEntity, string $toEntity): void
    {
        $bridgeTableName = $this->getBridgeTable($fromEntity, $toEntity);

        Schema::create($bridgeTableName, fn (Blueprint $table) => $table->increments('id'));

        $this->addForeignKey($bridgeTableName, $fromEntity, true);
        $this->addForeignKey($bridgeTableName, $toEntity, true);
    }

    public function dropBridgeTable(string $fromEntity, string $toEntity): void
    {
        $bridgeTableName = $this->getBridgeTable($fromEntity, $toEntity);

        $this->dropForeignKey($bridgeTableName, $fromEntity, true);
        $this->dropForeignKey($bridgeTableName, $toEntity, true);

        Schema::drop($bridgeTableName);
    }

    protected function getBridgeTable(string $fromEntity, string $toEntity): string
    {
        $entities = [Str::snake($fromEntity), Str::snake($toEntity)];
        sort($entities, SORT_STRING);

        return implode('_', $entities);
    }

    protected function getTableName(string $entityName): string
    {
        if (Schema::hasTable($entityName)) {
            return $entityName;
        }

        $entityName = Str::snake($entityName);

        return Str::plural($entityName);
    }
}
