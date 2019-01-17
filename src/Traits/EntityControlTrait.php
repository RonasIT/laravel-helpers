<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Exceptions\InvalidModelException;
use RonasIT\Support\Exceptions\PostValidationException;

trait EntityControlTrait
{
    use SearchTrait;

    protected $requiredRelations = [];
    protected $model;
    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $fields;
    protected $primaryKey;

    public function all()
    {
        return $this->get();
    }

    public function truncate()
    {
        $modelInstance = $this->model;

        $modelInstance::truncate();
    }

    public function setModel($newModel)
    {
        $this->model = $newModel;

        $modelInstance = new $this->model;

        $this->fields = $modelInstance::getFields();

        $this->primaryKey = $modelInstance->getKeyName();

        $this->checkPrimaryKey();
    }

    protected function getQuery()
    {
        $query = (new $this->model)->query();

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

        return $query;
    }

    public function withRelations(array $relations)
    {
        $this->requiredRelations = $relations;

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
        $query = $this->getQuery();

        if (is_array($where)) {
            return $query->where($where)->exists();
        }

        return $query->where($this->primaryKey, $where)->exists();
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
        return $this->getQuery()
            ->where($field, $value)
            ->exists();
    }

    public function create($data)
    {
        $modelInstance = $this->model;

        $newEntity = $modelInstance::create(array_only($data, $this->fields));

        if (!empty($this->requiredRelations)) {
            $newEntity->load($this->requiredRelations);
        }

        return $newEntity->refresh()->toArray();
    }

    /**
     * Update rows by condition or primary key
     *
     * @param array|integer $where
     * @param array $data
     *
     * @return array
     */
    public function updateMany($where, $data)
    {
        $query = $this->getQuery();

        $query->where($where)
            ->update(
                array_only($data, $this->fields)
            );

        $where = array_merge($where, $data);

        return $this->get($where);
    }

    public function update($where, $data = [])
    {
        $query = $this->getQuery();

        if (!is_array($where)) {
            $where = [
                $this->primaryKey => $where
            ];
        }

        $item = $query->where($where)->first();

        if (empty($item)) {
            return [];
        }

        $item->fill($data)->save();

        return $item->refresh()->toArray();
    }

    public function updateOrCreate($where, $data)
    {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        }

        return $this->create(array_merge($where, $data));
    }

    public function count($where = [])
    {
        return $this->getQuery()
            ->where($where)
            ->count();
    }

    /**
     * @param  array $where
     *
     * @return array
     */
    public function get($where = [])
    {
        $query = $this->getQuery()->where(array_only(
            $where, $this->fields
        ));

        $entity = $query->get();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function getOrCreate($data)
    {
        if ($this->exists($data)) {
            return $this->get($data);
        }

        return $this->create($data);
    }

    public function first($data)
    {
        $query = $this->getQuery()->where(array_only(
            $data, $this->fields
        ));

        $entity = $query->first();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function findBy($field, $value)
    {
        return $this->first([
            $field => $value
        ]);
    }

    public function find($id)
    {
        return $this->first([
            $this->primaryKey => $id
        ]);
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
     */
    public function delete($where)
    {
        if (is_array($where)) {
            $this->getQuery()
                ->where(array_only($where, $this->fields))
                ->delete();
        } else {
            $this->getQuery()
                ->where($this->primaryKey, $where)
                ->delete();
        }
    }

    public function forceDelete($id)
    {
        $this->getQuery()->find($id)->forceDelete();
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

    public function restore($id)
    {
        $this->getQuery()
            ->withTrashed()
            ->find($id)
            ->restore();
    }

    public function validateField($id, $field, $value)
    {
        $query = $this->getQuery()
            ->where('id', '<>', $id)
            ->where($field, $value);

        if ($query->exists()) {
            $message = "{$this->getEntityName()} with {$field} {$value} already exists";

            throw (new PostValidationException())->setData([
                $field => [$message]
            ]);
        }
    }

    protected function getEntityName()
    {
        $explodedModel = explode('\\', $this->model);

        return end($explodedModel);
    }

    protected function isSoftDelete()
    {
        $traits = class_uses($this->model);

        return in_array(SoftDeletes::class, $traits);
    }

    protected function checkPrimaryKey()
    {
        if (is_null($this->primaryKey)) {
            throw new InvalidModelException("Model {$this->model} must have primary key.");
        }
    }
}