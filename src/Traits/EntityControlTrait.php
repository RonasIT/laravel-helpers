<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 11:57
 */

namespace RonasIT\Support\Traits;

trait EntityControlTrait
{
    protected $model;
    protected $withTrashed = false;
    protected $fields;

    public function setModel($model) {
        $this->model = $model;

        $model = new $this->model;

        $this->fields = $model::getFields();
    }

    protected function getQuery() {
        $model = new $this->model;

        $query = $model->query();

        if ($this->withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }

    public function exists($data) {
        $query = $this->getQuery();

        return $query->where(array_only($data, $this->fields))->exists();
    }

    public function create($data) {
        $model = $this->model;

        return $model::create(array_only($data, $model::getFields()))->toArray();
    }

    public function update($where, $data) {
        $query = $this->getQuery();

        $query->where($where)
            ->update(
                array_only($data, $this->fields)
            );

        $where = array_merge($where, $data);

        return $this->get($where);
    }

    public function updateOrCreate($where, $data) {
        if ($this->exists($where)) {
            return $this->update($where, $data);
        } else {
            return $this->create(array_merge($where, $data));
        }
    }

    public function get($data) {
        return $this->getWithRelations($data, []);
    }

    public function getWithRelations($data, $with = []) {
        $query = $this->getQuery()->where(array_only(
            $data, $this->fields
        ));

        if (!empty($with)) {
            $query->with($with);
        }

        $entity = $query->get();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function first($data) {
        return $this->firstWithRelations($data, []);
    }

    public function firstWithRelations($data, $with = []) {
        $query = $this->getQuery()->where(array_only(
            $data, $this->fields
        ));

        if (!empty($with)) {
            $query->with($with);
        }

        $entity = $query->first();

        return empty($entity) ? [] : $entity->toArray();
    }

    public function delete($where) {
        $model = new $this->model;

        if (is_array($where)) {
            $model::where(array_only($where, $model::getFields()))
                ->delete();
        } else {
            $model::where('id', $where)->delete();
        }
    }

    public function withTrashed() {
        $this->withTrashed = true;

        return $this;
    }

    public function restore($id) {
        $this->getQuery()->find($id)->restore();
    }

    public function forceDelete($id) {
        $this->getQuery()->find($id)->forceDelete();
    }

    public function getOrCreate($data) {
        if ($this->exists($data)) {
            return $this->get($data);
        } else {
            return $this->create($data);
        }
    }

    public function firstOrCreate($data) {
        if ($this->exists($data)) {
            return $this->first($data);
        } else {
            return $this->create($data);
        }
    }

    public function count($where) {
        return $this->getQuery()
            ->where($where)
            ->count();
    }
}