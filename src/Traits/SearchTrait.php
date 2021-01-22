<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Builder as Query;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property Query query 
 */
trait SearchTrait
{
    protected $query;
    protected $filter;

    public function paginate()
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = Arr::get($this->filter, 'per_page', $defaultPerPage );
        $page = Arr::get($this->filter, 'page', 1);

        return $this->query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param $field string, filtered field, you can pass field name with dots to filter by field of relation
     * @param $filterName string|null, key from filters which contains filter value
     * @return $this
     */
    public function filterBy($field, $filterName = null)
    {
        if (empty($filterName)) {
            if (Str::contains($field, '.')) {
                $entities = explode('.', $field);
                $filterName = Arr::last($entities);
            } else {
                $filterName = $field;
            }
        }

        if (Arr::has($this->filter, $filterName)) {
            $this->addWhere($this->query, $field, $this->filter[$filterName]);
        }

        return $this;
    }

    public function filterByQuery(array $fields)
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields) {
                foreach ($fields as $field) {
                    if (Str::contains($field, '.')) {
                        $entities = explode('.', $field);
                        $fieldName = array_pop($entities);
                        $relations = implode('.', $entities);

                        $query->orWhereHas($relations, function ($query) use ($fieldName) {
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
            return $this->getModifiedPaginator($this->paginate())->toArray();
        }

        return $this->wrapPaginatedData($this->query->get()->makeHidden($this->hiddenAttributes)->makeVisible($this->visibleAttributes)->toArray());
    }

    public function getModifiedPaginator($paginator)
    {
        $modifiedCollection = $paginator
            ->getCollection()
            ->makeHidden($this->hiddenAttributes)
            ->makeVisible($this->visibleAttributes);

        return $paginator->setCollection($modifiedCollection);
    }

    public function orderBy($default = null, $defaultDesc = false)
    {
        $default = (empty($default)) ? $this->primaryKey : $default;

        $orderField = Arr::get($this->filter, 'order_by', $default);
        $isDesc = Arr::get($this->filter, 'desc', $defaultDesc);

        if (Str::contains($orderField, '.')) {
            $this->query->orderByRelated($orderField, $this->getDesc($isDesc));
        } else {
            $this->query->orderBy($orderField, $this->getDesc($isDesc));
        }

        if ($orderField != $default) {
            $this->query->orderBy($default, $this->getDesc($defaultDesc));
        }

        return $this;
    }

    protected function getDesc($isDesc)
    {
        return $isDesc ? 'DESC' : 'ASC';
    }

    /**
     * @deprecated
     *
     * Use filterBy() with dot notation instead
     */
    public function filterByRelationField($relation, $field, $filterName = null)
    {
        if (empty($filterName)) {
            $filterName = $field;
        }

        if (Arr::has($this->filter, $filterName)) {
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
        $url = Request::url();
        $path = Request::path();
        $total = count($data);

        return [
            'current_page' => 1,
            'data' => $data,
            'first_page_url' => "{$url}?page=1",
            'from' => 1,
            'last_page' => 1,
            'last_page_url' => "{$url}?page=1",
            'next_page_url' => null,
            'path' => $path,
            'per_page' => $total,
            'prev_page_url' => null,
            'to' => $total,
            'total' => $total
        ];
    }

    public function filterByList($field, $filterName)
    {
        if (Arr::has($this->filter, $filterName)) {
            $this->applyWhereCallback($this->query, $field, function (&$q, $conditionField) use ($filterName) {
                $q->whereIn($conditionField, $this->filter[$filterName]);
            });
        }

        return $this;
    }

    public function filterFrom($field, $strict = true, $filterName = null)
    {
        $filterName = empty($filterName) ? 'from' : $filterName;
        $sign = $strict ? '>' : '>=';
        
        if (!empty($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    public function filterTo($field, $strict = true, $filterName = null)
    {
        $filterName = empty($filterName) ? 'to' : $filterName;
        $sign = $strict ? '<' : '<=';
        
        if (!empty($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    public function withCount()
    {
        if (!empty($this->filter['with_count'])) {
            foreach ($this->filter['with_count'] as $requestedRelations) {
                $explodedRelation = explode('.', $requestedRelations);
                $countRelation = array_pop($explodedRelation);
                $relation = implode('.', $explodedRelation);

                if (empty($relation)) {
                    $this->query->withCount($countRelation);
                } else {
                    $this->query->with([
                        $relation => function ($query) use ($countRelation) {
                            $query->withCount($countRelation);
                        }
                    ]);
                }
            }
        }

        return $this;
    }

    public function getSearchQuery()
    {
        return $this->query;
    }

    protected function addWhere(&$query, $field, $value, $sign = '=')
    {
        $this->applyWhereCallback($query, $field, function (&$q, $field) use ($sign, $value) {
            $q->where($field, $sign, $value);
        });
    }

    protected function constructWhere($query, $where = [], $field = null)
    {
        if (!is_array($where)) {
            $field = (empty($field)) ? $this->primaryKey : $field;

            $where = [
                $field => $where
            ];
        }

        foreach ($where as $field => $value) {
            $this->addWhere($query, $field, $value);
        }

        return $query;
    }

    protected function applyWhereCallback($query, $field, $callback) {
        if (Str::contains($field, '.')) {
            $entities = explode('.', $field);
            $conditionField = array_pop($entities);
            $relations = implode('.', $entities);

            $query->whereHas($relations, function ($q) use ($callback, $conditionField) {
                $callback($q, $conditionField);
            });
        } else {
            $callback($query, $field);
        }
    }
}
