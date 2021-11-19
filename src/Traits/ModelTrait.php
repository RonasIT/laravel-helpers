<?php

namespace RonasIT\Support\Traits;

use BadMethodCallException;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Schema;

trait ModelTrait
{
    protected $disableLazyLoading = false;

    public static function getFields(): array
    {
        $model = (new static);

        $keyName = $model->getKeyName();
        $guarded = $model->getGuarded();
        $fillable = $model->getFillable();
        $timeStamps = ($model->timestamps) ? ['created_at', 'updated_at'] : [];

        array_unshift($fillable, $keyName);

        return array_merge($fillable, $guarded, $timeStamps);
    }

    public function getAllFieldsWithTable(): array
    {
        $fields = Schema::getColumnListing($this->getTable());

        return array_map(function ($field) {
            return "{$this->getTable()}.{$field}";
        }, $fields);
    }

    protected function getRelationshipFromMethod($method)
    {
        if ($this->disableLazyLoading) {
            $modelName = static::class;

            throw new BadMethodCallException(
                "Attempting to lazy-load relation '{$method}' on model '{$modelName}'. See property \$disableLazyLoading"
            );
        }

        return parent::getRelationshipFromMethod($method);
    }

    /**
     * This method was added, because native Laravel's method addSelect
     * overwrites existed select clause
     * @param $query
     * @param $fields
     *
     * @return mixed
     */
    public function scopeAddFieldsToSelect($query, array $fields = [])
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
    public function scopeOrderByRelated(QueryBuilder $query, string $relations, string $desc = 'DESC', ?string $asField = null, string $manyToManyStrategy = 'max'): QueryBuilder
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

            $this
                ->applyManyToManyStrategy($prevQuery, $manyToManyStrategy)
                ->select($orderField);

            foreach ($queries as $queryInCollection) {
                $prevQuery = $this
                    ->applyManyToManyStrategy($queryInCollection, $manyToManyStrategy)
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

    protected function prepareRelations(string $relations): array
    {
        if (Str::contains($relations, '.')) {
            return explode('.', $relations);
        } else {
            return [
                $relations
            ];
        }
    }

    private function getOrderedField(&$relations): string
    {
        if (is_array($relations)) {
            return array_pop($relations);
        }

        return $relations;
    }

    protected function getQueriesList(QueryBuilder $query, array $relations): array
    {
        $requiredColumns = [];
        $queryCollection = [$query];

        foreach ($relations as $relationString) {
            $query = Arr::last($queryCollection);

            $relation = $this->getRelationWithoutConstraints($query, $relationString);
            $subQuery = $relation->getRelationExistenceQuery(
                $relation->getQuery(),
                $query,
                $requiredColumns
            );

            $queryCollection[] = $subQuery;
        }

        return array_reverse($queryCollection);
    }

    protected function applyManyToManyStrategy(QueryBuilder $query, string $strategy): QueryBuilder
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
    public function scopeLegacyOrderByRelated(QueryBuilder $query, string $orderField, string $desc = 'DESC'): void
    {
        list ($fieldName, $relationName) = extract_last_part($orderField);

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
