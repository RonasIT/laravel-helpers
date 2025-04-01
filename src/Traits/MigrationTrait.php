<?php

namespace RonasIT\Support\Traits;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\StringType;
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
            'pgsql' => $this->changePostgresEnums($table, $field, $values, $valuesToRename),
            default => throw new Exception("Database driver \"{$databaseDriver}\" not available")
        };
    }

    private function changePostgresEnums(string $table, string $field, array $values, array $valuesToRename = []): void
    {
        $check = "{$table}_{$field}_check";

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$check}");

        foreach ($valuesToRename as $key => $value) {
            DB::table($table)->where($field, $key)->update([$field => $value]);
        }

        $values = $this->preparePostgresValues($values);

        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$check} CHECK ({$field}::text = ANY (ARRAY[{$values}]::text[]))");
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
