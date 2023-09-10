<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
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

    protected $attachedRelations = [];
    protected $attachedRelationsCount = [];

    protected $reservedFilters  = [
        'with',
        'with_count',
        'with_trashed',
        'only_trashed',
        'query',
        'order_by',
        'all',
        'per_page',
        'page',
        'desc'
    ];

    protected function setAdditionalReservedFilters(...$filterNames)
    {
        array_push($this->reservedFilters, ...$filterNames);
    }

    public function paginate(): LengthAwarePaginator
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = Arr::get($this->filter, 'per_page', $defaultPerPage);
        $page = Arr::get($this->filter, 'page', 1);

        return $this->query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param $field string filtered field, you can pass field name with dots to filter by field of relation
     * @param $filterName string|null key from filters which contains filter value
     *
     * @return self
     */
    public function filterBy(string $field, ?string $filterName = null): self
    {
        if (empty($filterName)) {
            if (Str::contains($field, '.')) {
                list ($filterName) = extract_last_part($field);
            } else {
                $filterName = $field;
            }
        }

        if (Arr::has($this->filter, $filterName)) {
            $values = Arr::wrap($this->filter[$filterName]);

            $this->applyWhereCallback($this->query, $field, function (&$query, $conditionField) use ($values) {
                $query->whereIn($conditionField, $values);
            });
        }

        return $this;
    }

    public function filterByQuery(array $fields, string $mask = "'%{{ value }}%'"): self
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields, $mask) {
                foreach ($fields as $field) {
                    if (Str::contains($field, '.')) {
                        list ($fieldName, $relations) = extract_last_part($field);

                        $query->orWhereHas($relations, function ($query) use ($fieldName, $mask) {
                            $query->where(
                                $this->getQuerySearchCallback($fieldName, $mask)
                            );
                        });
                    } else {
                        $query->orWhere(
                            $this->getQuerySearchCallback($field, $mask)
                        );
                    }
                }
            });
        }

        return $this;
    }

    public function searchQuery(array $filter = []): self
    {
        if (!empty($filter['with_trashed'])) {
            $this->withTrashed();
        }

        if (!empty($filter['only_trashed'])) {
            $this->onlyTrashed();
        }

        $this->query = $this
            ->with(Arr::get($filter, 'with', $this->attachedRelations))
            ->withCount(Arr::get($filter, 'with_count', $this->attachedRelationsCount))
            ->getQuery();

        $this->filter = $filter;

        foreach($filter as $fieldName => $value) {
            $isNotReservedFilter = (!in_array($fieldName, $this->reservedFilters));

            if ($isNotReservedFilter) {
                if (Str::endsWith($fieldName, '_not_in_list')) {
                    $field = Str::replace('_not_in_list', '', $fieldName);
                    $this->query->whereNotIn($field, $value);
                } elseif (Str::endsWith($fieldName, '_from')) {
                    $field = Str::replace('_from', '', $fieldName);
                    $this->filterFrom($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_to')) {
                    $field = Str::replace('_to', '', $fieldName);
                    $this->filterTo($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_in_list')) {
                    $field = Str::replace('_in_list', '', $fieldName);
                    $this->query->whereIn($field, $value);
                } else {
                    $this->filterBy($fieldName);
                }
            }
        }

        return $this;
    }

    public function getSearchResults(): LengthAwarePaginator
    {
        $this->orderBy();

        $this->postQueryHook();

        if (empty($this->filter['all'])) {
            return $this->getModifiedPaginator($this->paginate());
        }

        $data = $this->query->get();

        return $this->wrapPaginatedData($data);
    }

    public function wrapPaginatedData(Collection $data): LengthAwarePaginator
    {
        $total = $data->count();

        $perPage = $this->calculatePerPage($total);

        $paginator = new LengthAwarePaginator($data, count($data), $perPage, 1, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page'
        ]);

        return $this->getModifiedPaginator($paginator);
    }

    public function getModifiedPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $collection = $paginator->getCollection();

        return $paginator->setCollection($collection);
    }

    public function orderBy(?string $default = null, bool $defaultDesc = false): self
    {
        $default = (empty($default)) ? $this->primaryKey : $default;

        $orderField = Arr::get($this->filter, 'order_by', $default);
        $isDesc = Arr::get($this->filter, 'desc', $defaultDesc);

        if (Str::contains($orderField, '.')) {
            $this->query->orderByRelated($orderField, $this->getDesc($isDesc));
        } else {
            $this->query->orderBy($orderField, $this->getDesc($isDesc));
        }

        if ($orderField !== $default) {
            $this->query->orderBy($default, $this->getDesc($defaultDesc));
        }

        return $this;
    }

    protected function getDesc(bool $isDesc): string
    {
        return $isDesc ? 'DESC' : 'ASC';
    }

    public function filterMoreThan(string $field, $value): self
    {
        return $this->filterValue($field, '>', $value);
    }

    public function filterLessThan(string $field, $value): self
    {
        return $this->filterValue($field, '<', $value);
    }

    public function filterMoreOrEqualThan(string $field, $value): self
    {
        return $this->filterValue($field, '>=', $value);
    }

    public function filterLessOrEqualThan(string $field, $value): self
    {
        return $this->filterValue($field, '<=', $value);
    }

    public function filterValue(string $field, string $sign, $value): self
    {
        if (!empty($value)) {
            $this->query->where($field, $sign, $value);
        }

        return $this;
    }

    /**
     * @param $relations array|string
     *
     * @return $this
     */
    public function with($relations): self
    {
        $this->attachedRelations = Arr::wrap($relations);

        return $this;
    }

    /**
     * @param $relations array|string
     *
     * @return $this
     */
    public function withCount($relations): self
    {
        $this->attachedRelationsCount = Arr::wrap($relations);

        return $this;
    }

    protected function getQuerySearchCallback(string $field, string $mask): Closure
    {
        return function ($query) use ($field, $mask) {
            $databaseDriver = config('database.default');
            $value = str_replace('{{ value }}', $this->filter['query'], $mask);
            $operator = ($databaseDriver === 'pgsql')
                ? 'ilike'
                : 'like';

            $query->orWhere($field, $operator, DB::raw($value));
        };
    }

    public function filterFrom(string $field, bool $strict = true, ?string $filterName = null): self
    {
        $filterName = empty($filterName) ? 'from' : $filterName;
        $sign = $strict ? '>' : '>=';

        if (isset($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    public function filterTo(string $field, bool $strict = true, ?string $filterName = null): self
    {
        $filterName = empty($filterName) ? 'to' : $filterName;
        $sign = $strict ? '<' : '<=';

        if (isset($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    public function getSearchQuery(): Query
    {
        return $this->query;
    }

    protected function addWhere(Query &$query, string $field, $value, string $sign = '='): void
    {
        $this->applyWhereCallback($query, $field, function (&$query, $field) use ($sign, $value) {
            $query->where($field, $sign, $value);
        });
    }

    protected function constructWhere(Query $query, $where = [], ?string $field = null): Query
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

    protected function applyWhereCallback(Query $query, string $field, Closure $callback): void
    {
        if (Str::contains($field, '.')) {
            list ($conditionField, $relations) = extract_last_part($field);

            $query->whereHas($relations, function ($q) use ($callback, $conditionField) {
                $callback($q, $conditionField);
            });
        } else {
            $callback($query, $field);
        }
    }

    protected function calculatePerPage(int $total): int
    {
        if ($total > 0) {
            return $total;
        }

        if (!empty($this->filter['per_page'])) {
            return $this->filter['per_page'];
        }

        return config('defaults.items_per_page', 1);
    }

    protected function postQueryHook(): void
    {
        if ($this->shouldSettablePropertiesBeReset) {
            $this->onlyTrashed(false);
            $this->withTrashed(false);
            $this->force(false);
            $this->with([]);
            $this->withCount([]);
        }
    }
}
