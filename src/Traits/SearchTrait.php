<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

trait SearchTrait
{
    protected $query;
    protected $filter;

    public function paginate()
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = array_get($this->filter, 'per_page', $defaultPerPage );
        $page = array_get($this->filter, 'page', 1);

        return $this->query->paginate($perPage, ['*'], 'page', $page);
    }

    public function filterBy($field, $default = null)
    {
        if (!empty($default)) {
            $this->filter[$field] = array_get($this->filter, $field, $default);
        }

        if (array_has($this->filter, $field)) {
            $this->query->where($field, $this->filter[$field]);
        }

        return $this;
    }

    public function filterByQuery($fields)
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields) {
                foreach ($fields as $field) {
                    if (str_contains($field, '.')) {
                        $entities = explode('.', $field);
                        $fieldName = array_pop($entities);

                        $query->orWhereHas(implode('.', $entities), function ($query) use ($fieldName) {
                            $query->where(
                                $this->getQuerySearchCallback($fieldName)
                            );
                        });
                    } else {
                        $query->orWhere(
                            $this->getQuerySearchCallback($field)
                        );
                    }
                }
            });
        }

        return $this;
    }

    public function searchQuery($filter)
    {
        if (!empty($filter['with_trashed'])) {
            $this->withTrashed();
        }

        $this->query = $this->getQuery();

        $this->filter = $filter;

        return $this;
    }

    public function getSearchResults()
    {
        $this->orderBy();

        if (empty($this->filter['all'])) {
            return $this->paginate()->toArray();
        }

        return $this->wrapPaginatedData($this->query->get()->toArray());
    }

    public function orderBy($default = null, $defaultDesc = false) {
        $default = (empty($default)) ? $this->primaryKey : $default;
        $orderBy = array_get($this->filter, 'order_by', $default);
        $isDesc = array_get($this->filter, 'desc', $defaultDesc);

        $this->query->orderBy($orderBy, $this->getDesc($isDesc));

        if ($orderBy != $default) {
            $this->query->orderBy($default, $this->getDesc($defaultDesc));
        }

        return $this;
    }

    protected function getDesc($isDesc)
    {
        return $isDesc ? 'DESC' : 'ASC';
    }

    public function filterByRelationField($relation, $field, $filterName = null)
    {
        if (empty($filterName)) {
            $filterName = $field;
        }

        if (array_has($this->filter, $filterName)) {
            $this->query->whereHas($relation, function ($query) use ($field, $filterName) {
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

    public function filterMoreOrEqualThan($field, $value)
    {
        return $this->filterValue($field, '>=', $value);
    }

    public function filterLessOrEqualThan($field, $value)
    {
        return $this->filterValue($field, '<=', $value);
    }

    public function filterValue($field, $sign, $value)
    {
        if (!empty($value)) {
            $this->query->where($field, $sign, $value);
        }

        return $this;
    }

    public function with()
    {
        if (!empty($this->filter['with'])) {
            $this->query->with($this->filter['with']);
        }

        return $this;
    }

    protected function getQuerySearchCallback($field)
    {
        return function ($query) use ($field) {
            $loweredQuery = mb_strtolower($this->filter['query']);
            $field = DB::raw("lower({$field})");

            $query->orWhere($field, 'like', "%{$loweredQuery}%");
        };
    }

    protected function wrapPaginatedData($data)
    {
        $url = Request::path();
        $total = count($data);

        return [
            'current_page' => 1,
            'data' => $data,
            'first_page_url' => "{$url}?page=1",
            'from' => 1,
            'last_page' => 1,
            'last_page_url' => "{$url}?page=1",
            'next_page_url' => null,
            'path' => $url,
            'per_page' => $total,
            'prev_page_url' => null,
            'to' => $total,
            'total' => $total
        ];
    }
}