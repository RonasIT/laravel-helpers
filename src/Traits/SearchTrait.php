<?php
/**
 * Created by PhpStorm.
 * User: romandubrovin
 * Date: 05.09.17
 * Time: 7:37
 */

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;

trait SearchTrait
{
    protected $query;
    protected $filter;

    public function paginate($query, $data = [])
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = empty($data['per_page']) ? $defaultPerPage : $data['per_page'];

        return $query->paginate($perPage);
    }

    public function filterBy($field, $default = null)
    {
        if (!empty($default)) {
            $this->filter[$field] = array_get($this->filter, $field, $default);
        }

        if (!empty($this->filter[$field])) {
            $this->query->where($field, $this->filter[$field]);
        }

        return $this;
    }

    protected function filterByQuery($fields)
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields) {
                foreach ($fields as $field) {
                    $loweredQuery = mb_strtolower($this->filter['query']);
                    $field = DB::raw("lower({$field})");

                    $query->orWhere($field, 'like', "%{$loweredQuery}%");
                }
            });
        }

        return $this;
    }

    protected function searchQuery($filter)
    {
        if (!empty($filter['with_trashed'])) {
            $this->withTrashed();
        }

        $this->query = $this->getQuery();

        $this->filter = $filter;

        return $this;
    }

    protected function getSearchResults()
    {
        if (empty($this->filter['all'])) {
            $results = $this->paginate($this->query, $this->filter);
        } else {
            $results = $this->query->get();
        }

        return $results->toArray();
    }

    protected function orderBy()
    {
        if (!empty($this->filter['order_by'])) {
            $desk = $this->getDesc($this->filter);

            $this->query->orderBy($this->filter['order_by'], $desk);
        }

        return $this;
    }

    protected function getDesc($options = [])
    {
        $isDesc = array_get($options, 'desc', false);

        return $isDesc ? 'DESC' : 'ASC';
    }

    protected function filterByRelationField($relation, $field, $filterName = null)
    {
        if (empty($filterName)) {
            $filterName = $field;
        }

        if (array_has($this->filter, $filterName)) {
            $this->query->whereHas($relation, function($query) use ($field, $filterName) {
                $query->where(
                    $field, $this->filter[$filterName]
                );
            });
        }

        return $this;
    }

    public function filterMoreThan($field, $value)
    {
        return $this->filterValue($field, '>', $value);
    }

    public function filterLessThan($field, $value)
    {
        return $this->filterValue($field, '<', $value);
    }

    protected function filterValue($field, $sign, $value) {
        if (!empty($value)) {
            $this->query->where($field, $sign, $value);
        }

        return $this;
    }

    protected function with()
    {
        if (!empty($this->filter['with'])) {
            $this->query->with($this->filter['with']);
        }

        return $this;
    }
}