<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder as Query;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RonasIT\Support\Exceptions\InvalidModelException;

/**
 * @property Model model
 */
trait EntityControlTrait
{
    use SearchTrait;

    protected $model;
    protected $fields;
    protected $primaryKey;

    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $forceMode = false;

    protected $shouldSettablePropertiesBeReset = true;

    public function all(): Collection
    {
        return $this->get();
    }

    public function truncate(): self
    {
        $modelInstance = $this->model;

        $modelInstance::truncate();

        return $this;
    }

    public function force($value = true): self
    {
        $this->forceMode = $value;

        return $this;
    }

    public function setModel($modelClass): self
    {
        $this->model = new $modelClass();

        $this->fields = $modelClass::getFields();

        $this->primaryKey = $this->model->getKeyName();

        $this->checkPrimaryKey();

        return $this;
    }

    protected function getQuery($where = []): Query
    {
        $query = $this->model->query();

        if ($this->onlyTrashed) {
            $query->onlyTrashed();

            $this->withTrashed = false;
        }

        if ($this->withTrashed && $this->hasSoftDeleteTrait()) {
            $query->withTrashed();
        }

        if (!empty($this->attachedRelations)) {
            $query->with($this->attachedRelations);
        }

        if (!empty($this->attachedRelationsCount)) {
            foreach ($this->attachedRelationsCount as $requestedRelations) {
                list($countRelation, $relation) = extract_last_part($requestedRelations);

                if (empty($relation)) {
                    $query->withCount($countRelation);
                } else {
                    $query->with([$relation => fn ($query) => $query->withCount($countRelation)]);
                }
            }
        }

        $result = $this->constructWhere($query, $where);

        $this->postQueryHook();

        return $result;
    }

    /**
     * Check entity existing in database.
     *
     * @param  mixed  $where
     */
    public function exists($where): bool
    {
        return $this->getQuery($where)->exists();
    }

    /**
     * Checking that record with this key value exists
     */
    public function existsBy(string $field, $value): bool
    {
        return $this->getQuery([$field => $value])->exists();
    }

    public function create(array $data): Model
    {
        $entityData = Arr::only($data, $this->fields);
        $modelClass = get_class($this->model);
        $model = new $modelClass();

        if ($this->forceMode) {
            $model->forceFill($entityData);
        } else {
            $model->fill(Arr::only($entityData, $model->getFillable()));
        }

        $model->save();
        $model->refresh();

        if (!empty($this->attachedRelations)) {
            $model->load($this->attachedRelations);
        }

        $this->postQueryHook();

        return $model;
    }

    public function insert(array $data): bool
    {
        $defaultTimestamps = [];

        if ($this->model->timestamps) {
            $now = now();

            $defaultTimestamps = [
                $this->model::CREATED_AT => $now,
                $this->model::UPDATED_AT => $now,
            ];
        }

        $data = array_map(function ($item) use ($defaultTimestamps) {
            $fillableFields = Arr::only($item, $this->model->getFillable());

            return array_merge($defaultTimestamps, $fillableFields);
        }, $data);

        $this->postQueryHook();

        return $this->model->insert($data);
    }

    /**
     * Update rows by condition or primary key
     *
     * @param  mixed  $where
     */
    public function updateMany($where, array $data): int
    {
        $modelClass = get_class($this->model);
        $fields = $this->forceMode ? $modelClass::getFields() : $this->model->getFillable();
        $entityData = Arr::only($data, $fields);

        return $this->getQuery($where)->update($entityData);
    }

    /**
     * Update only one row by condition or primary key value
     *
     * @param  array|int  $where
     */
    public function update($where, array $data): ?Model
    {
        $forceMode = $this->forceMode;
        $relations = $this->attachedRelations;

        $item = $this->getQuery($where)->first();

        if (empty($item)) {
            return null;
        }

        if ($forceMode) {
            $item->forceFill(Arr::only($data, $this->fields));
        } else {
            $item->fill(Arr::only($data, $item->getFillable()));
        }

        $item->save();
        $item->refresh();

        if (!empty($relations)) {
            $item->load($relations);
        }

        return $item;
    }

    public function updateOrCreate($where, $data): Model
    {
        $this->resetSettableProperties(false);

        if ($this->exists($where)) {
            $this->resetSettableProperties();

            return $this->update($where, $data);
        }

        if (!is_array($where)) {
            $where = [$this->primaryKey => $where];
        }

        $this->resetSettableProperties();

        return $this->create(array_merge($data, $where));
    }

    public function count($where = []): int
    {
        return $this->getQuery($where)->count();
    }

    public function get(array $where = []): Collection
    {
        return $this->getQuery($where)->get();
    }

    public function first($where = []): ?Model
    {
        return $this->getQuery($where)->first();
    }

    public function last(array $where = [], string $column = 'created_at'): ?Model
    {
        return $this
            ->getQuery($where)
            ->latest($column)
            ->first();
    }

    public function findBy(string $field, $value): ?Model
    {
        return $this->first([$field => $value]);
    }

    public function find($id): ?Model
    {
        return $this->first($id);
    }

    /**
     * @param  array|string|int  $where  array of conditions or primary key value
     */
    public function firstOrCreate($where, array $data = []): Model
    {
        $this->resetSettableProperties(false);

        $entity = $this->first($where);

        $this->resetSettableProperties();

        if (empty($entity)) {
            return $this->create(array_merge($data, $where));
        }

        $this->postQueryHook();

        return $entity;
    }

    /**
     * Delete rows by condition or primary key
     *
     * @param  array|int|string  $where
     *
     * @return int count of deleted rows
     */
    public function delete($where): int
    {
        $forceMode = $this->forceMode;

        $query = $this->getQuery($where);

        if ($forceMode) {
            return $query->forceDelete();
        }

        return $query->delete();
    }

    public function withTrashed($enable = true): self
    {
        $this->withTrashed = $enable;

        return $this;
    }

    public function onlyTrashed($enable = true): self
    {
        $this->onlyTrashed = $enable;

        return $this;
    }

    public function restore($where): int
    {
        return $this->getQuery($where)->onlyTrashed()->restore();
    }

    public function chunk(int $limit, Closure $callback, array $where = []): void
    {
        $this
            ->getQuery($where)
            ->orderBy($this->primaryKey)
            ->chunk($limit, $callback);
    }

    /**
     * Delete rows by list of values a particular field or primary key
     *
     * @param  ?string  $field  condition field, primary key is default value
     *
     * @return int count of deleted rows
     */
    public function deleteByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;
        $forceMode = $this->forceMode;

        $query = $this
            ->getQuery()
            ->whereIn($field, $values);

        if ($forceMode && $this->hasSoftDeleteTrait()) {
            return $query->forceDelete();
        }

        return $query->delete();
    }

    public function restoreByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        return $this
            ->getQuery()
            ->onlyTrashed()
            ->whereIn($field, $values)
            ->restore();
    }

    public function getByList(array $values, ?string $field = null): Collection
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        return $this
            ->getQuery()
            ->whereIn($field, $values)
            ->get();
    }

    public function countByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        return $this->getQuery()->whereIn($field, $values)->count();
    }

    public function updateByList(array $values, array $data, $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;
        $forceMode = $this->forceMode;

        $query = $this->getQuery()->whereIn($field, $values);

        $fields = $forceMode ? $this->fields : $this->model->getFillable();

        return $query->update(Arr::only($data, $fields));
    }

    protected function getEntityName(): string
    {
        $explodedModel = explode('\\', get_class($this->model));

        return end($explodedModel);
    }

    protected function hasSoftDeleteTrait(): bool
    {
        $traits = class_uses(get_class($this->model));

        return in_array(SoftDeletes::class, $traits);
    }

    protected function checkPrimaryKey(): void
    {
        if (is_null($this->primaryKey)) {
            $modelClass = get_class($this->model);

            throw new InvalidModelException("Model {$modelClass} must have primary key.");
        }
    }

    protected function resetSettableProperties(bool $value = true): void
    {
        $this->shouldSettablePropertiesBeReset = $value;
    }
}
