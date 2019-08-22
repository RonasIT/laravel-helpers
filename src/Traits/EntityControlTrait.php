<?php

namespace RonasIT\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Exceptions\InvalidModelException;
use RonasIT\Support\Exceptions\PostValidationException;
use Illuminate\Support\Arr;

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

    public function all()
    {
        return $this->get();
    }

    public function truncate()
    {
        $modelInstance = $this->model;

        $modelInstance::truncate();
    }

    public function force($value = true)
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
            $query->withCount($this->requiredRelationsCount);
        }

        return $this->constructWhere($query, $where);
    }

    public function withRelations(array $relations)
    {
        $this->requiredRelations = $relations;

        return $this;
    }

    public function withRelationsCount($withCount)
    {
        $this->requiredRelationsCount = $withCount;

        return $this;
    }

    /**
     * Check entity existing in database.
     *
     * @param mixed $where
     *
     * @return boolean
     */
    public function exists($where)
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
    public function existsBy($field, $value)
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

        if (!empty($this->requiredRelations)) {
            $model->load($this->requiredRelations);
        }
        
        $this->afterCreateHook($model, $data);

        return $model->refresh()->toArray();
    }

    /**
     * Update rows by condition or primary key
     *
     * @param array|integer $where
     * @param array $data
     *
     * @return array
     */
    public function updateMany($where, array $data)
    {
        $modelClass = get_class($this->model);
        $fields = $this->forceMode ? $modelClass::getFields() : $this->model->getFillable();
        $entityData = Arr::only($data, $fields);

        $this
            ->getQuery()
            ->where($where)
            ->update($entityData);

        return $this->get(array_merge($where, $entityData));
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
            $item->forceFill(Arr::only($data, $this->fields))->save();
        } else {
            $item->fill(Arr::only($data, $item->getFillable()))->save();
        }

        $this->afterUpdateHook($item, $data);

        return $item->refresh()->toArray();
    }

    public function updateOrCreate($where, $data)
    {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        }

        if (!is_array($where)) {
            $where = [$this->primaryKey => $where];
        }

        return $this->create(array_merge($where, $data));
    }

    public function count($where = [])
    {
        return $this->getQuery($where)->count();
    }

    /**
     * @param  array $where
     *
     * @return array
     */
    public function get($where = [])
    {
        return $this->getQuery($where)->get()->toArray();
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

    public function first($where)
    {
        $entity = $this->getQuery($where)->first();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function findBy($field, $value)
    {
        return $this->first([$field => $value]);
    }

    public function find($id)
    {
        return $this->first([$this->primaryKey => $id]);
    }

    public function firstOrCreate($where, $data = [])
    {
        $entity = $this->first($where);

        if (empty($entity)) {
            return $this->create(array_merge($where, $data));
        }

        return $entity;
    }

    /**
     * Delete rows by condition or primary key
     *
     * @param array|integer|string $where
     * @return integer
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

    /**
     * @deprecated
     */
    public function forceDelete($where)
    {
        $this->getQuery($where)->forceDelete();
    }

    public function withTrashed($enable = true)
    {
        $this->withTrashed = $enable;

        return $this;
    }

    public function onlyTrashed($enable = true)
    {
        $this->onlyTrashed = $enable;

        return $this;
    }

    public function restore($where)
    {
        return $this->getQuery($where)->onlyTrashed()->restore();
    }

    public function validateField($id, $field, $value)
    {
        $query = $this
            ->getQuery()
            ->where('id', '<>', $id)
            ->where($field, $value);

        if ($query->exists()) {
            $message = "{$this->getEntityName()} with {$field} {$value} already exists";

            throw (new PostValidationException())->setData([
                $field => [$message]
            ]);
        }
    }

    public function chunk($limit, $callback, $where = [])
    {
        $this
            ->getQuery($where)
            ->orderBy($this->primaryKey)
            ->chunk($limit, function ($items) use ($callback) {
                $callback($items->toArray());
            });
    }

    public function deleteByList($values, $field = null): int
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

        $entities = $query->get()->toArray();

        $query->restore();

        return $entities;
    }

    public function getByList(array $values, $field = null)
    {
        $field = (empty($field)) ? $this->primaryKey : $field;

        return $this->getQuery()->whereIn($field, $values)->get()->toArray();
    }

    public function countByList(array $values, $field = null)
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

    protected function isSoftDelete()
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
