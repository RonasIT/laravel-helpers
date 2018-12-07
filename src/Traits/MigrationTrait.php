<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 02.07.17
 * Time: 22:06
 */

namespace RonasIT\Support\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait MigrationTrait
{
    public function addForeignKey($fromEntity, $toEntity, $needAddField = false)
    {
        Schema::table($this->getTableName($fromEntity), function (Blueprint $table) use ($toEntity, $needAddField) {
            $fieldName = snake_case($toEntity) . '_id';

            if ($needAddField) {
                $table->integer($fieldName);
            }

            $table->foreign($fieldName)
                ->references('id')
                ->on($this->getTableName($toEntity))
                ->onDelete('cascade');
        });
    }

    public function dropForeignKey($fromEntity, $toEntity, $needDropField = false)
    {
        $field = snake_case($toEntity) . '_id';
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

        Schema::create($bridgeTableName, function (Blueprint $table) use ($fromEntity, $toEntity) {
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
        $entities = [snake_case($fromEntity), snake_case($toEntity)];
        sort($entities, SORT_STRING);

        return implode('_', $entities);
    }

    protected function getTableName($entityName)
    {
        if (Schema::hasTable($entityName)) {
            return $entityName;
        }
        $entityName = snake_case($entityName);

        return Str::plural($entityName);
    }
}