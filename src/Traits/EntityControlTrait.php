<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder as Query;
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
                list ($countRelation, $relation) = extract_last_part($requestedRelations);

                if (empty($relation)) {
                    $query->withCount($countRelation);
                } else {
                    $query->with([
                        $relation => function ($query) use ($countRelation) {
                            $query->withCount($countRelation);
                        }
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
        return $this->getQuery($where)->exists();
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

        $this->afterCreateHook($model, $data);

        if (!empty($this->attachedRelations)) {
            $model->load($this->attachedRelations);
        }

        return $model;
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

        return $this->getQuery($where)->update($entityData);
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
            return null;
        }

        if ($this->forceMode) {
            $item->forceFill(Arr::only($data, $this->fields));
        } else {
            $item->fill(Arr::only($data, $item->getFillable()));
        }

        $item->save();
        $item->refresh();

        $this->afterUpdateHook($item, $data);

        if (!empty($this->attachedRelations)) {
            $item->load($this->attachedRelations);
        }

        return $item;
    }

    public function updateOrCreate($where, $data): Model
    {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        }

        if (!is_array($where)) {
            $where = [$this->primaryKey => $where];
        }

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
        $entity = $this->first($where);

        if (empty($entity)) {
            return $this->create(array_merge($data, $where));
        }

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
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
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
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
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

        $query = $this->getQuery()->whereIn($field, $values);

        $fields = $this->forceMode ? $this->fields : $this->model->getFillable();

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

    /**
     * @deprecated Method was implemented to have an ability to call some model-related methods inside the services class
     * but since version 2.0 services classes works with he models directly and no need to call hooks
     * @param Model|null $entity
     * @param array $data
     * @return void
     */
    protected function afterUpdateHook(?Model $entity, array $data)
    {
        // implement it yourself if you need it
    }

    /**
     * @deprecated Method was implemented to have an ability to call some model-related methods inside the services class
     * but since version 2.0 services classes works with he models directly and no need to call hooks
     * @param Model|null $entity
     * @param array $data
     * @return void
     */
    protected function afterCreateHook(?Model $entity, array $data)
    {
        // implement it yourself if you need it
    }
}
