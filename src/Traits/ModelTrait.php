<?php

namespace RonasIT\Support\Traits;

use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Schema;

trait ModelTrait
{
    protected static $forceVisible = [];
    protected static $forceHidden = [];

    public static function setForceVisibleFields($fields)
    {
        self::$forceVisible = $fields;
    }

    public static function setForceHiddenFields($fields)
    {
        self::$forceHidden = $fields;
    }

    public static function getFields()
    {
        $model = (new static);

        $keyName = $model->getKeyName();
        $guarded = $model->getGuarded();
        $fillable = $model->getFillable();
        $timeStamps = ($model->timestamps) ? ['created_at', 'updated_at'] : [];

        array_unshift($fillable, $keyName);

        return array_merge($fillable, $guarded, $timeStamps);
    }

    public function toArray()
    {
        $hidden = array_merge($this->hidden, self::$forceHidden);
        $this->setHidden(array_subtraction($hidden, self::$forceVisible));

        return parent::toArray();
    }

    public function getAllFieldsWithTable()
    {
        $fields = Schema::getColumnListing($this->getTable());

        return array_map(function ($field) {
            return "{$this->getTable()}.{$field}";
        }, $fields);
    }

    /**
     * This method was added, because native laravel's method addSelect
     * overwrites existed select clause
     * @param $query
     * @param $fields
     * @return mixed
     */
    public function scopeAddFieldsToSelect($query, $fields = null)
    {
        if (is_null($query->getQuery()->columns)) {
            $query->addSelect("{$this->getTable()}.*");
        }

        if (empty($fields)) {
            return $query;
        }

        return $query->addSelect($fields);
    }

    /**
     * Add orderBy By related field,
     * $manyToManyStrategy is affect oneToMany and ManyToMany Relations make orderBy('id', ASC/DESC)
     *
     * @param $query
     * @param $relations
     * @param string $desc
     * @param string $asField
     * @param string $manyToManyStrategy
     *
     * @return QueryBuilder
     */
    public function scopeOrderByRelated($query, $relations, $desc = 'DESC', $asField = null, $manyToManyStrategy = 'max')
    {
        if (version_compare(app()::VERSION, '5.5') <= 0) {
            return $query->legacyOrderByRelated($relations, $desc);
        }

        if (empty($asField)) {
            $asField = str_replace('.', '_', $relations);
        }

        $relations = $this->prepareRelations($relations);
        $orderField = $this->getOrderedField($relations);

        if (!empty($relations)) {
            $queries = $this->getQueriesList($query, $relations);
            $prevQuery = array_shift($queries);
            array_pop($queries);

            $this->applyManyToManyStrategy($prevQuery, $manyToManyStrategy)
                ->select($orderField);

            foreach ($queries as $queryInCollection) {
                $prevQuery = $this->applyManyToManyStrategy($queryInCollection, $manyToManyStrategy)
                    ->selectSub($prevQuery, $asField);
            }

            $query->addFieldsToSelect();
            $query->selectSub($prevQuery, $asField);
        }

        return $query->orderBy($asField ?? $orderField, $desc);
    }

    protected function getRelationWithoutConstraints($query, $relation)
    {
        return Relation::noConstraints(function () use ($query, $relation) {
            return $query->getModel()->{$relation}();
        });
    }

    protected function prepareRelations($relations)
    {
        if (str_contains($relations, '.')) {
            return explode('.', $relations);
        } else {
            return [
                $relations
            ];
        }
    }

    private function getOrderedField(&$relations)
    {
        if (is_array($relations)) {
            return array_pop($relations);
        }

        return $relations;
    }

    protected function getQueriesList($query, $relations)
    {
        $requiredColumns = [];
        $queryCollection = [
            $query
        ];

        foreach ($relations as $relationString) {
            $query = array_last($queryCollection);

            $relation = $this->getRelationWithoutConstraints($query, $relationString);
            $subQuery = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQueryWithoutRelationships(),
                $query,
                $requiredColumns
            );

            $queryCollection[] = $subQuery;
        }

        return array_reverse($queryCollection);
    }

    protected function applyManyToManyStrategy($query, $strategy)
    {
        if ($strategy === 'max') {
            $query->orderBy('id', 'ASC')->limit(1);
        } else {
            $query->orderBy('id', 'DESC')->limit(1);
        }

        return $query;
    }

    /*
     * Unfortunately, Laravel older than 5.5 does not support Relation::noConstraints so for such versions we
     * have to use simplified version of orderByRelated which does not support nesting relations.
     */
    public function scopeLegacyOrderByRelated($query, $orderField, $desc = 'DESC')
    {
        $entities = explode('.', $orderField);

        $fieldName = array_pop($entities);
        $relationName = array_shift($entities);

        if (Str::plural($relationName) !== $relationName) {
            $table = $this->getTable();
            $relation = $this->__callStatic($relationName, []);

            $relatedTable = $relation->getRelated()->getTable();
            $foreignKey = $relation->getForeignKey();
            $ownerKey = $relation->getOwnerKey();

            $rawQuery = DB::raw("(SELECT {$fieldName} FROM {$relatedTable} WHERE {$foreignKey} = {$relatedTable}.{$ownerKey} ) as orderedField");
            $query
                ->addSelect("{$table}.*", $rawQuery)
                ->orderBy('orderedField', $desc);
        }
    }

}
