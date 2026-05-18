<?php

namespace RonasIT\Support\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait ModelTrait
{
    protected bool $disableLazyLoading = true;

    public static function getFields(): array
    {
        $model = (new static());

        $keyName = $model->getKeyName();
        $guarded = $model->getGuarded();
        $fillable = $model->getFillable();
        $timeStamps = ($model->timestamps) ? ['created_at', 'updated_at'] : [];

        array_unshift($fillable, $keyName);

        return array_merge($fillable, $guarded, $timeStamps);
    }

    public function getAllFieldsWithTable(): array
    {
        $tableName = $this->getTable();
        $fields = Schema::getColumnListing($tableName);

        return array_map(fn ($field) => "{$tableName}.{$field}", $fields);
    }

    /**
     * This method was added, because native Laravel's method addSelect
     * overwrites existed select clause
     */
    public function scopeAddFieldsToSelect(Builder $query, array $fields = []): mixed
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
     */
    public function scopeOrderByRelated(
        Builder $query,
        string $relations,
        string $desc = 'DESC',
        ?string $asField = null,
        string $manyToManyStrategy = 'max',
    ): mixed {
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

    public function wasExchanged(string $fieldName): bool
    {
        return $this->wasChanged($fieldName)
            && !is_null($this->origin($fieldName))
            && !is_null($this->getAttribute($fieldName));
    }

    public function wasFilled(string $fieldName): bool
    {
        return $this->wasChanged($fieldName) && is_null($this->origin($fieldName));
    }

    public function wasCleared(string $fieldName): bool
    {
        return $this->wasChanged($fieldName) && is_null($this->getAttribute($fieldName));
    }

    public function origin(string $fieldName): mixed
    {
        return Arr::get($this->getPrevious(), $fieldName);
    }

    protected function getRelationshipFromMethod($method)
    {
        if ($this->disableLazyLoading) {
            $modelName = static::class;

            throw new BadMethodCallException(
                message: "Attempting to lazy-load relation '{$method}' on model '{$modelName}'. "
                . 'See property $disableLazyLoading.',
            );
        }

        return parent::getRelationshipFromMethod($method);
    }

    protected function getRelationWithoutConstraints(Builder $query, string $relation): Relation
    {
        return Relation::noConstraints(fn () => call_user_func([$query->getModel(), $relation]));
    }

    protected function prepareRelations(string $relations): array
    {
        if (Str::contains($relations, '.')) {
            return explode('.', $relations);
        } else {
            return [
                $relations,
            ];
        }
    }

    protected function getOrderedField(&$relations): string
    {
        if (is_array($relations)) {
            return array_pop($relations);
        }

        return $relations;
    }

    protected function getQueriesList(Builder $query, array $relations): array
    {
        $requiredColumns = [];
        $queryCollection = [$query];

        foreach ($relations as $relationString) {
            $query = Arr::last($queryCollection);

            $relation = $this->getRelationWithoutConstraints($query, $relationString);
            $subQuery = $relation->getRelationExistenceQuery(
                $relation->getQuery(),
                $query,
                $requiredColumns,
            );

            $queryCollection[] = $subQuery;
        }

        return array_reverse($queryCollection);
    }

    protected function applyManyToManyStrategy(Builder $query, string $strategy): Builder
    {
        if ($strategy === 'max') {
            $query->orderBy('id', 'ASC')->limit(1);
        } else {
            $query->orderBy('id', 'DESC')->limit(1);
        }

        return $query;
    }
}
