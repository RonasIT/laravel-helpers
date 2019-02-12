<?php

namespace RonasIT\Support\Traits;

use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Schema;

trait ModelTrait
{
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
        $relations = $this->prepareRelations($relations);
        $orderField = $this->getOrderedField($relations);

        if (!empty($relations)) {
            $queries = $this->getQueriesCollection($query, $relations);
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

    protected function getQueriesCollection($query, $relations)
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
}