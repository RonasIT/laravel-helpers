<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Exceptions\InvalidModelException;
use RonasIT\Support\Exceptions\PostValidationException;

/**
 * @property Model model
 */
trait EntityControlTrait
{
    use SearchTrait;

    protected $requiredRelations = [];
    protected $requiredRelationsCount = [];
    protected $model;
    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $fields;
    protected $primaryKey;
    protected $forceMode;
    protected $visibleAttributes = [];
    protected $hiddenAttributes = [];

    public function all()
    {
        return $this->get();
    }

    public function truncate()
    {
        $modelInstance = $this->model;

        $modelInstance::truncate();
    }

    public function force($value = true): self
    {
        $this->forceMode = $value;

        return $this;
    }

    public function setModel($modelClass)
    {
        $this->model = new $modelClass();

        $this->fields = $modelClass::getFields();

        $this->primaryKey = $this->model->getKeyName();

        $this->checkPrimaryKey();
    }

    /**
     * @param array|string $hiddenAttributes
     * @return $this
     */
    public function makeHidden($hiddenAttributes = []): self
    {
        $this->hiddenAttributes = Arr::wrap($hiddenAttributes);

        return $this;
    }

    /**
     * @param array|string $visibleAttributes
     * @return $this
     */
    public function makeVisible($visibleAttributes = []): self
    {
        $this->visibleAttributes = Arr::wrap($visibleAttributes);

        return $this;
    }

    protected function getQuery($where = [])
    {
        $query = $this->model->query();

        if ($this->onlyTrashed) {
            $query->onlyTrashed();

            $this->withTrashed = false;
        }

        if ($this->withTrashed && $this->isSoftDelete()) {
            $query->withTrashed();
        }

        if (!empty($this->requiredRelations)) {
            $query->with($this->requiredRelations);
        }

        if (!empty($this->requiredRelationsCount)) {
            foreach ($this->requiredRelationsCount as $requestedRelations) {
                $explodedRelation = explode('.', $requestedRelations);
                $countRelation = array_pop($explodedRelation);
                $relation = implode('.', $explodedRelation);

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
     * @param $relations array|string
     *
     * @return $this
     */
    public function withRelations($relations): self
    {
        $this->requiredRelations = Arr::wrap($relations);

        return $this;
    }

    /**
     * @param $relations array|string
     *
     * @return $this
     */
    public function withRelationsCount($relations): self
    {
        $this->requiredRelationsCount = Arr::wrap($relations);

        return $this;
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
     * @param $field
     * @param $value
     *
     * @return boolean
     */
    public function existsBy($field, $value): bool
    {
        return $this->getQuery([$field => $value])->exists();
    }

    public function create($data)
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

        if (!empty($this->requiredRelations)) {
            $model->load($this->requiredRelations);
        }

        return $model
            ->makeHidden($this->hiddenAttributes)
            ->makeVisible($this->visibleAttributes)
            ->toArray();
    }

    /**
     * Update rows by condition or primary key
     *
     * @param array|integer $where
     * @param array $data
     * @param bool $updatedRecordsAsResult
     * @param int $limit
     *
     * @return array|int
     */
    public function updateMany($where, array $data, bool $updatedRecordsAsResult = true, int $limit = 50)
    {
        $modelClass = get_class($this->model);
        $fields = $this->forceMode ? $modelClass::getFields() : $this->model->getFillable();
        $entityData = Arr::only($data, $fields);

        $idsToUpdate = [];

        $this->chunk($limit, function ($items) use (&$idsToUpdate) {
            $idsToUpdate = array_merge($idsToUpdate, Arr::pluck($items, 'id'));
        }, $where);

        $updatedRowsCount = $this->updateByList($idsToUpdate, $entityData);

        if (!$updatedRecordsAsResult) {
            return $updatedRowsCount;
        }

        return $this->getByList($idsToUpdate);
    }

    /**
     * Update only one row by condition or primary key value
     *
     * @param array|integer $where
     * @param array $data
     *
     * @return array
     */
    public function update($where, array $data)
    {
        $item = $this->getQuery($where)->first();

        if (empty($item)) {
            return [];
        }

        if ($this->forceMode) {
            $item->forceFill(Arr::only($data, $this->fields));
        } else {
            $item->fill(Arr::only($data, $item->getFillable()));
        }

        $item->save();
        $item->refresh();

        $this->afterUpdateHook($item, $data);

        if (!empty($this->requiredRelations)) {
            $item->load($this->requiredRelations);
        }

        return $item
            ->makeHidden($this->hiddenAttributes)
            ->makeVisible($this->visibleAttributes)
            ->toArray();
    }

    public function updateOrCreate($where, $data)
    {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        }

        if (!is_array($where)) {
            $where = [$this->primaryKey => $where];
        }

        return $this->create(array_merge($data, $where));
    }

    public function count($where = [])
    {
        return $this->getQuery($where)->count();
    }

    public function get(array $where = [])
    {
        $result = $this->getQuery($where)->get();

        $this->applyHidingShowingFieldsRules($result);

        return $result->toArray();
    }

    /**
     * @deprecated
     */
    public function getOrCreate($data)
    {
        $entities = $this->get($data);

        if (empty($entities)) {
            return $this->create($data);
        }

        return $entities;
    }

    public function first($where = [])
    {
        $entity = $this->getQuery($where)->first();

        return empty($entity) ? [] : $entity
            ->makeHidden($this->hiddenAttributes)
            ->makeVisible($this->visibleAttributes)
            ->toArray();
    }

    public function findBy(string $field, $value)
    {
        return $this->first([$field => $value]);
    }

    public function find($id)
    {
        return $this->first([$this->primaryKey => $id]);
    }

    /**
     * @param array|string|int $where array of conditions or primary key value
     * @param array $data
     *
     * @return array|mixed
     */
    public function firstOrCreate($where, array $data = [])
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
     * @return integer count of deleted rows
     */
    public function delete($where)
    {
        $query = $this->getQuery($where);

        if ($this->forceMode) {
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
    }

    /**
     * @deprecated
     */
    public function forceDelete($where)
    {
        $this->getQuery($where)->forceDelete();
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

    public function restore($where)
    {
        return $this->getQuery($where)->onlyTrashed()->restore();
    }

    public function chunk($limit, $callback, $where = [])
    {
        $this
            ->getQuery($where)
            ->orderBy($this->primaryKey)
            ->chunk($limit, function ($items) use ($callback) {
                $this->applyHidingShowingFieldsRules($items);

                $callback($items->toArray());
            });
    }

    /**
     * Delete rows by list of values a particular field or primary key
     *
     * @param array $values
     * @param string|null $field condition field, primary key is default value
     * @return integer count of deleted rows
     */
    public function deleteByList(array $values, $field = null)
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this
            ->getQuery()
            ->whereIn($field, $values);

        if ($this->forceMode && $this->isSoftDelete()) {
            return $query->forceDelete();
        } else {
            return $query->delete();
        }
    }

    public function restoreByList($values, $field = null)
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this
            ->getQuery()
            ->onlyTrashed()
            ->whereIn($field, $values);

        $entities = $query->get();

        $this->applyHidingShowingFieldsRules($entities);

        $query->restore();

        return $entities->toArray();
    }

    public function getByList(array $values, $field = null)
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $result = $this
            ->getQuery()
            ->whereIn($field, $values)
            ->get();

        $this->applyHidingShowingFieldsRules($result);

        return $result->toArray();
    }

    public function countByList(array $values, $field = null): int
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        return $this->getQuery()->whereIn($field, $values)->count();
    }

    public function updateByList(array $values, $data, $field = null)
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        $query = $this->getQuery()->whereIn($field, $values);

        $fields = $this->forceMode ? $this->fields : $this->model->getFillable();

        return $query->update(Arr::only($data, $fields));
    }

    protected function getEntityName()
    {
        $explodedModel = explode('\\', get_class($this->model));

        return end($explodedModel);
    }

    protected function isSoftDelete(): bool
    {
        $traits = class_uses(get_class($this->model));

        return in_array(SoftDeletes::class, $traits);
    }

    protected function checkPrimaryKey()
    {
        if (is_null($this->primaryKey)) {
            $modelClass = get_class($this->model);

            throw new InvalidModelException("Model {$modelClass} must have primary key.");
        }
    }

    protected function afterUpdateHook($entity, $data)
    {
        // implement it yourself if you need it
    }

    protected function afterCreateHook($entity, $data)
    {
        // implement it yourself if you need it
    }
}
