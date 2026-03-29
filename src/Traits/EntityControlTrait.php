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
 * @property Model $model
 */
trait EntityControlTrait
{
    use SearchTrait;

    protected Model $model;
    protected array $fields;
    protected ?string $primaryKey;

    protected bool $withTrashed = false;
    protected bool $onlyTrashed = false;
    protected bool $forceMode = false;

    protected bool $shouldSettablePropertiesBeReset = true;

    /**
     * Get all entities without conditions
     */
    public function all(): Collection
    {
        return $this->get();
    }

    /**
     * Remove all rows from the table
     */
    public function truncate(): self
    {
        $modelInstance = $this->model;

        $modelInstance::truncate();

        return $this;
    }

    /**
     * Enable force mode to bypass fillable restrictions
     */
    public function force(bool $value = true): self
    {
        $this->forceMode = $value;

        return $this;
    }

    /**
     * Set the model class for the repository
     */
    public function setModel(string $modelClass): self
    {
        $this->model = new $modelClass();

        $this->fields = $modelClass::getFields();

        $this->primaryKey = $this->model->getKeyName();

        $this->checkPrimaryKey();

        return $this;
    }

    /**
     * Check entity existence by condition or primary key
     */
    public function exists(array|int|string $where): bool
    {
        $result = $this->getQuery($where)->exists();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Check entity existence by a specific field value
     */
    public function existsBy(string $field, mixed $value): bool
    {
        $result = $this->getQuery([$field => $value])->exists();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Create a new entity and return it with loaded relations
     */
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

    /**
     * Mass insert rows with automatic timestamps
     */
    public function insert(array $data): bool
    {
        $result = $this->model->insert($this->prepareInsertData($data));

        $this->postQueryHook();

        return $result;
    }

    /**
     * Insert rows ignoring duplicate key errors. Return count of inserted rows
     */
    public function insertOrIgnore(array $data): int
    {
        $result = $this->model->insertOrIgnore($this->prepareInsertData($data));

        $this->postQueryHook();

        return $result;
    }

    /**
     * Update multiple entities by condition or primary key
     */
    public function updateMany(array|int|string $where, array $data): int
    {
        $modelClass = get_class($this->model);
        $fields = $this->forceMode ? $modelClass::getFields() : $this->model->getFillable();
        $entityData = Arr::only($data, $fields);

        $result = $this->getQuery($where)->update($entityData);

        $this->postQueryHook();

        return $result;
    }

    /**
     * Update a single entity by condition or primary key
     */
    public function update(array|int|string $where, array $data): ?Model
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

    /**
     * Update an existing entity or create a new one
     */
    public function updateOrCreate(array|int|string $where, array $data): Model
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

    /**
     * Count entities by condition or primary key
     */
    public function count(array|int|string $where = []): int
    {
        $result = $this->getQuery($where)->count();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Get entities by condition or primary key
     */
    public function get(array|int|string $where = []): Collection
    {
        $result = $this->getQuery($where)->get();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Get the first entity that matches the given condition or primary key
     */
    public function first(array|int|string $where = []): ?Model
    {
        $result = $this->getQuery($where)->first();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Get the last entity matching the given condition or primary key, ordered by the specified column
     */
    public function last(array|int|string $where = [], string $column = 'created_at'): ?Model
    {
        $result = $this
            ->getQuery($where)
            ->latest($column)
            ->first();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Find an entity by a specific field value
     */
    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->first([$field => $value]);
    }

    /**
     * Find an entity by primary key
     */
    public function find(int|string $id): ?Model
    {
        return $this->first($id);
    }

    /**
     * Get the first entity matching the condition or create a new one
     */
    public function firstOrCreate(array|string|int $where, array $data = []): Model
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
     * Delete entities by condition or primary key. Return count of deleted rows
     */
    public function delete(array|int|string $where): int
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

    /**
     * Include soft-deleted entities in queries
     */
    public function withTrashed(bool $enable = true): self
    {
        $this->withTrashed = $enable;

        return $this;
    }

    /**
     * Query only soft-deleted entities
     */
    public function onlyTrashed(bool $enable = true): self
    {
        $this->onlyTrashed = $enable;

        return $this;
    }

    /**
     * Restore soft-deleted entities by condition or primary key
     */
    public function restore(array|int|string $where): int
    {
        $result = $this->getQuery($where)->onlyTrashed()->restore();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Process entities in chunks ordered by primary key
     */
    public function chunk(int $limit, Closure $callback, array $where = []): void
    {
        $this
            ->getQuery($where)
            ->orderBy($this->primaryKey)
            ->chunk($limit, $callback);

        $this->postQueryHook();
    }

    /**
     * Delete entities by list of field values. Return count of deleted rows
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

    /**
     * Restore soft-deleted entities by list of field values
     */
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

    /**
     * Get entities by list of field values
     */
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

    /**
     * Count entities by list of field values
     */
    public function countByList(array $values, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $result = $this->getQuery()->whereIn($field, $values)->count();

        $this->postQueryHook();

        return $result;
    }

    /**
     * Update entities by list of field values
     */
    public function updateByList(array $values, array $data, ?string $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this->getQuery()->whereIn($field, $values);

        $fields = $this->forceMode ? $this->fields : $this->model->getFillable();

        $this->postQueryHook();

        return $query->update(Arr::only($data, $fields));
    }

    protected function getQuery(array|int|string $where = []): Query
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

        return $this->constructWhere($query, $where);
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

    protected function prepareInsertData(array $data): array
    {
        $defaultTimestamps = [];

        if ($this->model->timestamps) {
            $now = now();

            $defaultTimestamps = [
                $this->model::CREATED_AT => $now,
                $this->model::UPDATED_AT => $now,
            ];
        }

        return array_map(function (array $item) use ($defaultTimestamps) {
            $fillableFields = Arr::only($item, $this->model->getFillable());

            return array_merge($defaultTimestamps, $fillableFields);
        }, $data);
    }
}
