<?php

namespace RonasIT\Support\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait MigrationTrait
{
    public function addForeignKey($fromEntity, $toEntity, $needAddField = false, $onDelete = 'cascade')
    {
        Schema::table(
            $this->getTableName($fromEntity),
            function (Blueprint $table) use ($toEntity, $needAddField, $onDelete) {
                $fieldName = Str::snake($toEntity) . '_id';

                if ($needAddField) {
                    $table->unsignedInteger($fieldName);
                }

                $table->foreign($fieldName)
                    ->references('id')
                    ->on($this->getTableName($toEntity))
                    ->onDelete($onDelete);
            });
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
