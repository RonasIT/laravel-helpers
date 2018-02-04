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
    public function addForeignKey($fromTable, $toTable) {
        Schema::table($this->getTableName($fromTable), function (Blueprint $table) use ($toTable) {
            $fieldName = strtolower($toTable) . '_id';

            $table->foreign($fieldName)
                ->references('id')
                ->on($this->getTableName($toTable))
                ->onDelete('cascade');
        });
    }

    public function createBridgeTable($fromTable, $toTable) {
        $from = strtolower($fromTable);
        $to = strtolower($toTable);

        $bridgeTableName = $this->getBridgeTable($fromTable, $toTable);

        $this->createTable($bridgeTableName, [
            'integer-required' => [
                "{$from}_id", "{$to}_id"
            ]
        ]);

        $this->addForeignKey($bridgeTableName, $fromTable);
        $this->addForeignKey($bridgeTableName, $toTable);
    }

    public function dropBridgeTables($fromTable, $toTables) {
        Schema::drop($this->getBridgeTable($fromTable, $toTables));
    }

    public function addField($table, $field) {
        $field = strtolower($field) . '_id';
        $table = $this->getTableName($table);

        if (!Schema::hasColumn($table, $field)) {
            Schema::table($table, function (Blueprint $table) use ($field) {
                $table->integer($field);
            });
        }
    }

    protected function getBridgeTable($fromTable, $toTable) {
        $from = strtolower($fromTable);
        $to = strtolower($toTable);

        return $this->getTableName("{$from}_{$to}");
    }

    protected function getTableName($entityName) {
        $entityName = snake_case($entityName);

        return Str::plural($entityName);
    }
}