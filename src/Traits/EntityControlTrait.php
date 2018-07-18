<?php

namespace RonasIT\Support\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use RonasIT\Support\Exceptions\PostValidationException;

trait EntityControlTrait
{
    use SearchTrait;

    protected $model;
    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $fields;
    protected $primaryKey;

    public function setModel($model)
    {
        $this->model = $model;

        $model = new $this->model;

        $this->fields = $model::getFields();

        $this->primaryKey = $model->getKeyName();
    }

    protected function getQuery()
    {
        $model = new $this->model;

        $query = $model->query();

        if ($this->onlyTrashed) {
            $query->onlyTrashed();

            $this->withTrashed = false;
        }

        if ($this->withTrashed && $this->isSoftDelete()) {
            $query->withTrashed();
        }

        return $query;
    }

    public function all()
    {
        return $this->get();
    }

    public function exists($where)
    {
        $query = $this->getQuery();

        if(is_array($where)) {
            return $query->where($where)->exists();
        }

        return $query->where($this->primaryKey, $where)->exists();
    }

    public function existsBy($field, $value)
    {
        return $this->getQuery()
            ->where($field, $value)
            ->exists();
    }

    /**
     * @param array $fields
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function create($fields)
    {
        $model = $this->model;
        $this->checkPrimaryKey();

        $newEntity = $model::create(array_only($fields, $model::getFields()));

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
    public function update($where, $data)
    {
        if (is_array($where)) {
            return $this->updateByCondition($where, $data);
        }

        return $this->updateByPrimary($where, $data);
    }

    protected function updateByPrimary($value, $data)
    {
        $this->checkPrimaryKey();

        $query = $this->getQuery();

        $row = $query->where($this->primaryKey, $value)->first();

        if (empty($row)) {
            return [];
        }

        $row->fill($data)->save();

        return $row->refresh()->toArray();
    }

    protected function updateByCondition($where, $data)
    {
        $query = $this->getQuery();

        $query->where($where)
            ->update(
                array_only($data, $this->fields)
            );

        $where = array_merge($where, $data);

        return $this->get($where);
    }

    public function updateOrCreate($where, $data)
    {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        } else {
            return $this->create(array_merge($where, $data));
        }
    }

    /**
     * @param  array $where
     *
     * @return array
     */

    public function get($where = [])
    {
        return $this->getWithRelations($where, []);
    }

    /**
     * @param $data
     * @param array $with
     *
     * @return array
     */
    public function getWithRelations($data, $with = [])
    {
        $query = $this->getQuery()->where(array_only(
            $data, $this->fields
        ));

        if (!empty($with)) {
            $query->with($with);
        }

        $entity = $query->get();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function first($data)
    {
        return $this->firstWithRelations($data, []);
    }

    /**
     * @param $data
     * @param array $with
     *
     * @return array
     */
    public function firstWithRelations($data, $with = [])
    {
        $query = $this->getQuery()->where(array_only(
            $data, $this->fields
        ));

        if (!empty($with)) {
            $query->with($with);
        }

        $entity = $query->first();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function findBy($field, $value, $relations = [])
    {
        return $this->firstWithRelations([
            $field => $value
        ], $relations);
    }

    public function find($id, $relations = [])
    {
        return $this->firstWithRelations([
            $this->primaryKey => $id
        ], $relations);
    }

    /**
     * Delete rows by condition or primary key
     *
     * @param array $where
     */
    public function delete($where)
    {
        $model = new $this->model;

        if (is_array($where)) {
            $model::where(array_only($where, $model::getFields()))
                ->delete();
        } else {
            $model::where($this->primaryKey, $where)->delete();
        }
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
        $this->getQuery()->withTrashed()->find($id)->restore();
    }

    public function forceDelete($id)
    {
        $this->getQuery()->find($id)->forceDelete();
    }

    public function getOrCreate($data)
    {
        if ($this->exists($data)) {
            return $this->get($data);
        } else {
            return $this->create($data);
        }
    }

    public function firstOrCreate($data)
    {
        if ($this->exists($data)) {
            return $this->first($data);
        } else {
            return $this->create($data);
        }
    }

    /**
     * @param array $where
     *
     * @return mixed
     */
    public function count($where=[])
    {
        return $this->getQuery()
            ->where($where)
            ->count();
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

    public function truncate()
    {
        $model = $this->model;

        $model::truncate();
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
            throw new Exception("Model {$this->model} must have primary key.");
        }
    }
}