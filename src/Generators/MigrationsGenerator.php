<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:36
 */

namespace RonasIT\Support\Generators;

use Illuminate\Support\Str;
use RonasIT\Support\Events\SuccessCreateMessage;

class MigrationsGenerator extends EntityGenerator
{
    protected $name;
    protected $fields;
    protected $relations;
    protected $migrations;

    /** @return $this */
    public function setName($name) {
        $this->name = snake_case($name);
        return $this;
    }

    /** @return $this */
    public function setFields($fields) {
        $this->fields = $fields;
        return $this;
    }

    /** @return $this */
    public function setRelations($relations) {
        $this->relations = $relations;
        return $this;
    }

    public function generate() {
        $this->resolveNeededMigrations();

        foreach ($this->migrations as $migration) {
            $createMessage = "Created a new Migration: {$migration['name']}" ;

            $this->saveClass('migrations', $migration['name'], $migration['content']);

            event(new SuccessCreateMessage($createMessage));
        }
    }

    protected function resolveNeededMigrations() {
        $this->migrations[] = $this->generateCreateTableMigration($this->name, $this->fields);
        $this->resolveHasOneMigrations();
        $this->resolveHasManyMigrations();
        $this->resolveBelongsToMigrations();
        $this->resolveBelongsToManyMigrations();
    }

    protected function resolveHasOneMigrations() {
        foreach ($this->relations['hasOne'] as $relation) {
            $relation = snake_case($relation);
            $fieldName = strtolower($this->name).'_id';

            $this->migrations[] = $this->generateAddFieldMigration($relation, $fieldName, 'integer');
            $this->migrations[] = $this->generateForeignKeyMigration($relation, $fieldName, $this->name);
        }
    }

    protected function resolveHasManyMigrations() {
        foreach ($this->relations['hasMany'] as $relation) {
            $relation = snake_case($relation);
            $fieldName = strtolower($this->name).'_id';

            $this->migrations[] = $this->generateAddFieldMigration($relation, $fieldName, 'integer');
            $this->migrations[] = $this->generateForeignKeyMigration($relation, $fieldName, $this->name);
        }
    }

    protected function resolveBelongsToMigrations() {
        foreach ($this->relations['belongsTo'] as $relation) {
            $relation = snake_case($relation);
            $fieldName = strtolower($relation).'_id';

            $this->migrations[] = $this->generateAddFieldMigration($this->name, $fieldName, 'integer');
            $this->migrations[] = $this->generateForeignKeyMigration($this->name, $fieldName, $relation);
        }
    }

    protected function resolveBelongsToManyMigrations() {
        foreach ($this->relations['belongsToMany'] as $relation) {
            $relation = snake_case($relation);
            $localKey = strtolower($this->name).'_id';
            $otherKey = strtolower($relation).'_id';
            $fields = [
                'integer-required' => [$localKey, $otherKey]
            ];
            $bridgeTable = "{$this->getTableName($this->name)}_{$this->getTableName($relation)}";

            $this->migrations[] = $this->generateCreateTableMigration($bridgeTable, $fields);
            $this->migrations[] = $this->generateForeignKeyMigration($bridgeTable, $localKey, $this->name);
            $this->migrations[] = $this->generateForeignKeyMigration($bridgeTable, $otherKey, $relation);
        }
    }

    protected function generateForeignKeyMigration($fromTable, $fieldName, $toTable) {
        $namePieces = [
            $this->getNewMigrationTimestamp(),
            'add_foreign_key_from',
            $this->getTableName($fromTable),
            'to',
            $this->getTableName($toTable)
        ];

        return [
            'name' => implode('_', $namePieces),
            'content' => $this->getStub('migrations.foreign_key', [
                'SomeTable' => $this->prepareEntityName($fromTable),
                'AnotherTable' => $this->prepareEntityName($toTable),
                'some_table' => $this->getTableName($fromTable),
                'local_key' => $fieldName,
                'other_table' => $this->getTableName($toTable)
            ])
        ];
    }

    protected function generateAddFieldMigration($entityName, $field, $type, $isRequired = true) {
        $namePieces = [
            $this->getNewMigrationTimestamp(), 'add', $field, 'to', $this->getTableName($entityName)
        ];
        $fieldCamelName = Str::camel($field);

        $replaces = [
            'entities' => $this->getTableName($entityName),
            'Entities' => $this->prepareEntityName($entityName),
            'field' => $field,
            'Field' => ucfirst($fieldCamelName),
            'type' => $type
        ];

        if ($isRequired) {
            $replaces['->nullable()'] = '';
        }

        return [
            'name' => implode('_', $namePieces),
            'content' => $this->getStub('migrations.add_field', $replaces)
        ];
    }

    protected function generateCreateTableMigration($entityName, $fields) {
        $namePieces = [
            $this->getNewMigrationTimestamp(), 'create', $this->getTableName($entityName), 'table'
        ];

        return [
            'name' => implode('_', $namePieces),
            'content' => $this->getStub('migrations.create', [
                'Entity' => $this->prepareEntityName($entityName),
                'entities' => $this->getTableName($entityName),
                '/*fields*/' => $this->getFieldsContent($fields)
            ])
        ];
    }

    protected function prepareEntityName($entityName) {
        $camelName = Str::camel($entityName);
        $entityName = ucfirst($camelName);

        return Str::plural($entityName);
    }

    protected function getNewMigrationTimestamp() {
        static $currentTimestamp;

        $lastMigration = $this->getLastMigrationName();

        $explodedMigrationName = explode('_', $lastMigration);

        if (empty($currentTimestamp)) {
            $explodedMigrationName[3] += 1;

            $currentTimestamp = $explodedMigrationName[3];
        } else {
            $explodedMigrationName[3] = ++$currentTimestamp;
        }

        return implode('_', array_splice($explodedMigrationName, 0, 4));
    }

    protected function getLastMigrationName() {
        $migrations = scandir($this->paths['migrations']);

        return array_last($migrations);
    }

    protected function getFieldsContent($entityFields) {
        $fieldsContent = '';

        foreach ($entityFields as $type => $fields) {
            $explodedType = explode('-', $type);

            $type = $explodedType[0];
            $isRequired = array_has($explodedType, '1');

            foreach ($fields as $field) {
                $replaces = [
                    'type' => $type,
                    'field' => $field
                ];

                if ($isRequired) {
                    $replaces['->nullable()'] = '';
                }

                $fieldsContent .= $this->getStub('migrations.field', $replaces);
            }
        }

        return $fieldsContent;
    }
}