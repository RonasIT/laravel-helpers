<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder as Query;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property Query $query
 */
trait SearchTrait
{
    protected Query $query;
    protected array $filter;

    protected array $attachedRelations = [];
    protected array $attachedRelationsCount = [];

    protected array $reservedFilters = [
        'with',
        'with_count',
        'with_trashed',
        'only_trashed',
        'query',
        'order_by',
        'all',
        'per_page',
        'page',
        'desc',
    ];

    /**
     * Paginate the query using per_page and page from filters
     */
    public function paginate(): LengthAwarePaginator
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = Arr::get($this->filter, 'per_page', $defaultPerPage);
        $page = Arr::get($this->filter, 'page', 1);

        return $this->query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Applies filtering by the specified field. Supports dot notation for related fields
     */
    public function filterBy(string $field, ?string $filterName = null): self
    {
        $filterName ??= $this->getFilterName($field);

        if (Arr::has($this->filter, $filterName)) {
            $this->applyWhereCallback($this->query, $field, function (&$query, $conditionField) use ($filterName) {
                $query->where($conditionField, $this->filter[$filterName]);
            });
        }

        return $this;
    }

    /**
     * Filter by a list of values (whereIn). Supports dot notation for relations
     */
    public function filterByList(string $field, ?string $filterName = null): self
    {
        $filterName ??= $this->getFilterName($field);

        if (Arr::has($this->filter, $filterName)) {
            $this->applyWhereCallback($this->query, $field, function (&$query, $conditionField) use ($filterName) {
                $query->whereIn($conditionField, $this->filter[$filterName]);
            });
        }

        return $this;
    }

    /**
     * Search by text query (LIKE) across multiple fields. Supports dot notation for relations
     */
    public function filterByQuery(array $fields, string $mask = "'%{{ value }}%'"): self
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields, $mask) {
                foreach ($fields as $field) {
                    if (Str::contains($field, '.')) {
                        list($fieldName, $relations) = extract_last_part($field);

                        $query->orWhereHas($relations, function ($query) use ($fieldName, $mask) {
                            $query->where($this->getQuerySearchCallback($fieldName, $mask));
                        });
                    } else {
                        $query->orWhere($this->getQuerySearchCallback($field, $mask));
                    }
                }
            });
        }

        return $this;
    }

    /**
     * Initialize the search query and auto-apply filters
     */
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

        foreach ($filter as $fieldName => $value) {
            $isNotReservedFilter = (!in_array($fieldName, $this->reservedFilters));

            if ($isNotReservedFilter) {
                if (Str::endsWith($fieldName, '_not_in_list')) {
                    $field = Str::replace('_not_in_list', '', $fieldName);
                    $this->query->whereNotIn($field, $value);
                } elseif (Str::endsWith($fieldName, '_gte')) {
                    $field = Str::replace('_gte', '', $fieldName);
                    $this->filterGreater($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_gt')) {
                    $field = Str::replace('_gt', '', $fieldName);
                    $this->filterGreater($field, true, $fieldName);
                } elseif (Str::endsWith($fieldName, '_lte')) {
                    $field = Str::replace('_lte', '', $fieldName);
                    $this->filterLess($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_lt')) {
                    $field = Str::replace('_lt', '', $fieldName);
                    $this->filterLess($field, true, $fieldName);
                } elseif (Str::endsWith($fieldName, '_from')) {
                    $field = Str::replace('_from', '', $fieldName);
                    $this->filterFrom($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_to')) {
                    $field = Str::replace('_to', '', $fieldName);
                    $this->filterTo($field, false, $fieldName);
                } elseif (Str::endsWith($fieldName, '_in_list')) {
                    $field = Str::replace('_in_list', '', $fieldName);
                    $this->filterByList($field, $fieldName);
                } else {
                    $this->filterBy($fieldName);
                }
            }
        }

        return $this;
    }

    /**
     * Finalize the search: apply ordering and return paginated results
     */
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

    /**
     * Wrap a collection into a LengthAwarePaginator with a single page
     */
    public function wrapPaginatedData(Collection $data): LengthAwarePaginator
    {
        $total = $data->count();

        $perPage = $this->calculatePerPage($total);

        $paginator = new LengthAwarePaginator($data, $total, $perPage, 1, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        return $this->getModifiedPaginator($paginator);
    }

    /**
     * Hook for modifying the paginator before returning results
     */
    public function getModifiedPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $collection = $paginator->getCollection();

        return $paginator->setCollection($collection);
    }

    /**
     * Sort results by the order_by filter. Supports dot notation for relations
     */
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

    /** @deprecated use filterGreater instead */
    public function filterMoreThan(string $field, mixed $value): self
    {
        return $this->filterValue($field, '>', $value);
    }

    /** @deprecated use filterLess instead */
    public function filterLessThan(string $field, mixed $value): self
    {
        return $this->filterValue($field, '<', $value);
    }

    /** @deprecated use filterGreater instead */
    public function filterMoreOrEqualThan(string $field, mixed $value): self
    {
        return $this->filterValue($field, '>=', $value);
    }

    /** @deprecated use filterLess instead */
    public function filterLessOrEqualThan(string $field, mixed $value): self
    {
        return $this->filterValue($field, '<=', $value);
    }

    /**
     * Add a where condition with a comparison operator
     */
    public function filterValue(string $field, string $sign, mixed $value): self
    {
        if (!empty($value)) {
            $this->query->where($field, $sign, $value);
        }

        return $this;
    }

    /**
     * Set relations for eager loading
     */
    public function with(array|string $relations): self
    {
        $this->attachedRelations = Arr::wrap($relations);

        return $this;
    }

    /**
     * Set relations for counting
     */
    public function withCount(array|string $relations): self
    {
        $this->attachedRelationsCount = Arr::wrap($relations);

        return $this;
    }

    /** @deprecated use filterGreater instead */
    public function filterFrom(string $field, bool $isStrict = true, ?string $filterName = null): self
    {
        return $this->filterGreater($field, $isStrict, $filterName);
    }

    /**
     * Filter where field is greater than (or equal to) the filter value
     */
    public function filterGreater(string $field, bool $isStrict = true, ?string $filterName = null): self
    {
        $filterName = empty($filterName) ? 'from' : $filterName;
        $sign = ($isStrict) ? '>' : '>=';

        if (isset($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    /** @deprecated use filterLess instead */
    public function filterTo(string $field, bool $isStrict = true, ?string $filterName = null): self
    {
        return $this->filterLess($field, $isStrict, $filterName);
    }

    /**
     * Filter where field is less than (or equal to) the filter value
     */
    public function filterLess(string $field, bool $isStrict = true, ?string $filterName = null): self
    {
        $filterName = (empty($filterName)) ? 'to' : $filterName;
        $sign = ($isStrict) ? '<' : '<=';

        if (isset($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    /**
     * Get the current Eloquent query builder
     */
    public function getSearchQuery(): Query
    {
        return $this->query;
    }

    protected function setAdditionalReservedFilters(string ...$filterNames): void
    {
        array_push($this->reservedFilters, ...$filterNames);
    }

    protected function getDesc(bool $isDesc): string
    {
        return ($isDesc) ? 'DESC' : 'ASC';
    }

    protected function getQuerySearchCallback(string $field, string $mask): Closure
    {
        return function ($query) use ($field, $mask) {
            $databaseDriver = config('database.default');
            $value = ($databaseDriver === 'pgsql')
                ? pg_escape_string($this->filter['query'])
                : addslashes($this->filter['query']);
            $value = str_replace('{{ value }}', $value, $mask);
            $operator = ($databaseDriver === 'pgsql')
                ? 'ilike'
                : 'like';

            $query->orWhere($field, $operator, DB::raw($value));
        };
    }

    protected function addWhere(Query &$query, string $field, mixed $value, string $sign = '='): void
    {
        $this->applyWhereCallback($query, $field, fn (&$query, $field) => $query->where($field, $sign, $value));
    }

    protected function constructWhere(Query $query, array|int|string $where = [], ?string $field = null): Query
    {
        if (!is_array($where)) {
            $field = (empty($field)) ? $this->primaryKey : $field;

            $where = [
                $field => $where,
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
            list($conditionField, $relations) = extract_last_part($field);

            $query->whereHas($relations, fn ($q) => $callback($q, $conditionField));
        } else {
            $callback($query, $field);
        }
    }

    protected function calculatePerPage(int $total): int
    {
        if ($total > 0) {
            return $total;
        }

        $defaultPerPage = config('defaults.items_per_page', 1);

        return Arr::get($this->filter, 'per_page', $defaultPerPage);
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

    protected function getFilterName(string $field): string
    {
        if (Str::contains($field, '.')) {
            list($field) = extract_last_part($field);
        }

        return $field;
    }
}
