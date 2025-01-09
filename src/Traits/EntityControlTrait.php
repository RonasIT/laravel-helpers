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
                    $query->with([
                        $relation => function ($query) use ($countRelation) {
                            $query->withCount($countRelation);
                        },
                    ]);
                }
            }
        }

        return $this->constructWhere($query, $where);
    }

    /**
     * Check entity existing in database.
     *
     * @param mixed $where
     *
     * @return boolean
     */
    public function exists($where): bool
    {
        $result = $this->getQuery($where)->exists();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Checking that record with this key value exists
     *
     * @param string $field
     * @param $value
     *
     * @return boolean
     */
    public function existsBy(string $field, $value): bool
    {
        $result = $this->getQuery([$field => $value])->exists();

        $this->postQueryHook();

        return $result;
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
                $this->model::UPDATED_AT => $now
            ];
        }

        $data = array_map(function ($item) use ($defaultTimestamps) {
            $fillableFields = Arr::only($item, $this->model->getFillable());

            $timestamps = array_merge($defaultTimestamps, Arr::only($fillableFields, [
                $this->model::CREATED_AT,
                $this->model::UPDATED_AT
            ]));

            return array_merge($fillableFields, $timestamps);
        }, $data);

        $this->postQueryHook();

        return $this->model->insert($data);
    }

    /**
     * Update rows by condition or primary key
     *
     * @param mixed $where
     * @param array $data
     *
     * @return int
     */
    public function updateMany($where, array $data): int
    {
        $modelClass = get_class($this->model);
        $fields = $this->forceMode ? $modelClass::getFields() : $this->model->getFillable();
        $entityData = Arr::only($data, $fields);

        $result = $this->getQuery($where)->update($entityData);

        $this->postQueryHook();

        return $result;
    }

    /**
     * Update only one row by condition or primary key value
     *
     * @param array|integer $where
     * @param array $data
     *
     * @return Model
     */
    public function update($where, array $data): ?Model
    {
        $item = $this->getQuery($where)->first();

        if (empty($item)) {
            $this->postQueryHook();

            return null;
        }

        if ($this->forceMode) {
            $item->forceFill(Arr::only($data, $this->fields));
        } else {
            $item->fill(Arr::only($data, $item->getFillable()));
        }

        $item->save();
        $item->refresh();

        if (!empty($this->attachedRelations)) {
            $item->load($this->attachedRelations);
        }

        $this->postQueryHook();

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
        $result = $this->getQuery($where)->count();

        $this->postQueryHook();

        return $result;
    }

    public function get(array $where = []): Collection
    {
        $result = $this->getQuery($where)->get();

        $this->postQueryHook();

        return $result;
    }

    public function first($where = []): ?Model
    {
        $result = $this->getQuery($where)->first();

        $this->postQueryHook();

        return $result;
    }

    public function last(array $where = [], string $column = 'created_at'): ?Model
    {
        $result = $this
            ->getQuery($where)
            ->latest($column)
            ->first();

        $this->postQueryHook();

        return $result;
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
     * @param array|string|int $where array of conditions or primary key value
     * @param array $data
     *
     * @return Model
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
     * @param array|integer|string $where
     *
     * @return integer count of deleted rows
     */
    public function delete($where): int
    {
        $query = $this->getQuery($where);

        if ($this->forceMode) {
            $result = $query->forceDelete();
        } else {
            $result = $query->delete();
        }

        $this->postQueryHook();

        return $result;
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
        $result = $this->getQuery($where)->onlyTrashed()->restore();

        $this->postQueryHook();

        return $result;
    }

    public function chunk(int $limit, Closure $callback, array $where = []): void
    {
        $this
            ->getQuery($where)
            ->orderBy($this->primaryKey)
            ->chunk($limit, $callback);

        $this->postQueryHook();
    }

    /**
     * Delete rows by list of values a particular field or primary key
     *
     * @param array $values
     * @param ?string $field condition field, primary key is default value
     *
     * @return integer count of deleted rows
     */
    public function deleteByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this
            ->getQuery()
            ->whereIn($field, $values);

        if ($this->forceMode && $this->hasSoftDeleteTrait()) {
            $result = $query->forceDelete();
        } else {
            $result = $query->delete();
        }

        $this->postQueryHook();

        return $result;
    }

    public function restoreByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $result = $this
            ->getQuery()
            ->onlyTrashed()
            ->whereIn($field, $values)
            ->restore();

        $this->postQueryHook();

        return $result;
    }

    public function getByList(array $values, ?string $field = null): Collection
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $result = $this
            ->getQuery()
            ->whereIn($field, $values)
            ->get();

        $this->postQueryHook();

        return $result;
    }

    public function countByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $result = $this->getQuery()->whereIn($field, $values)->count();

        $this->postQueryHook();

        return $result;
    }

    public function updateByList(array $values, array $data, $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this->getQuery()->whereIn($field, $values);

        $fields = $this->forceMode ? $this->fields : $this->model->getFillable();

        $this->postQueryHook();

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
