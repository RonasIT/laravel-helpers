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
    public function scopeAddFieldsToSelect($query, $fields)
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
     * @param $orderField
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
            $builders = $this->getBuildersCollection($relations, $query, $manyToManyStrategy);
            $prevBuilder = array_shift($builders);
            array_pop($builders);

            $this->applyManyToManyStrategy($prevBuilder, $manyToManyStrategy)
                ->select($orderField)
                ->limit(1);

            foreach ($builders as $builder) {
                $prevBuilder = $this->applyManyToManyStrategy($builder, $manyToManyStrategy)
                    ->selectSub($prevBuilder, $asField)
                    ->limit(1);
            }

            $query->addFieldsToSelect($query->getQuery()->columns);
            $query->selectSub($prevBuilder, $asField);
        }

        return $query->orderBy($asField ?? $orderField, $desc);
    }

    protected function getRelationWithoutConstraints($query, $relation)
    {
        return Relation::noConstraints(function () use ($query, $relation) {
            return $query->getModel()->{$relation}();
        });
    }

    private function prepareRelations($relations)
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

    private function getBuildersCollection($relations, $query)
    {
        $requiredColumns = [];
        $buildersCollection = [
            $query
        ];

        foreach ($relations as $relationString) {
            $query = array_last($buildersCollection);

            $relation = $this->getRelationWithoutConstraints($query, $relationString);
            $subQuery = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQueryWithoutRelationships(),
                $query,
                $requiredColumns
            );

            $buildersCollection[] = $subQuery;
        }

        return array_reverse($buildersCollection);
    }

    private function applyManyToManyStrategy($query, $strategy)
    {
        if ($strategy === 'max') {
            $query->orderBy('id', 'ASC');
        } else {
            $query->orderBy('id', 'DESC');
        }

        return $query;
    }
}