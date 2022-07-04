<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
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
        'query',
        'order_by',
        'all',
        'per_page',
        'page',
        'desc',
        'from',
        'to'
    ];

    protected $listPostfixs = [
        '_in_list',
        '_not_in_list'
    ];

    public function paginate(): LengthAwarePaginator
    {
        $defaultPerPage = config('defaults.items_per_page');
        $perPage = Arr::get($this->filter, 'per_page', $defaultPerPage);
        $page = Arr::get($this->filter, 'page', 1);

        return $this->query->paginate($perPage, ['*'], 'page', $page);
    }

    public function filterByQuery(array $fields): self
    {
        if (!empty($this->filter['query'])) {
            $this->query->where(function ($query) use ($fields) {
                foreach ($fields as $field) {
                    if (Str::contains($field, '.')) {
                        list ($fieldName, $relations) = extract_last_part($field);

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

    public function searchQuery(array $filter): self
    {
        if (!empty($filter['with_trashed'])) {
            $this->withTrashed();
        }

        $this->query = $this->getQuery();

        $this->filter = $filter;

        if (!empty($this->filter)) {
            foreach ($this->filter as $field => $values) {
                if (!in_array($field, $this->reservedFilters) && is_array($values) || !in_array($field, $this->reservedFilters) && is_string($values)) {
                    $notInList = strpos($field, $this->listPostfixs[1]);

                    if ($notInList) {
                        $this->filter($this->listPostfixs[1], 'whereNotIn', $field, $values);
                    } else {
                        $this->filter($this->listPostfixs[0], 'whereIn', $field, $values);
                    }
                }
            }
        }

        return $this;
    }

    protected function filter($postfix, $where, $field, $values)
    {
        $fieldWithoutPostfix = Str::replace($postfix, '', $field);

        if (!is_array($values) || !Arr::isAssoc($values)) {
            $this->query->{$where}($fieldWithoutPostfix, Arr::wrap($values));
        } else {
            foreach ($values as $relationFiled => $value) {
                $fieldWithRelation = $fieldWithoutPostfix . '.' . $relationFiled;
            }

            $this->applyWhereCallback($this->query, $fieldWithRelation, function (&$query, $conditionField) use ($value, $where) {
                $query->{$where}($conditionField, Arr::wrap($value));
            });
        }
    }

    public function getSearchResults(): LengthAwarePaginator
    {
        $this->orderBy();

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

    protected function getQuerySearchCallback(string $field): Closure
    {
        $databaseDriver = config('database.default');
        $dbRawValue = "lower({$field})";

        if ($databaseDriver === 'pgsql') {
            $dbRawValue = "lower(text({$field}))";
        }

        return function ($query) use ($dbRawValue) {
            $loweredQuery = mb_strtolower($this->filter['query']);
            $field = DB::raw($dbRawValue);

            $query->orWhere($field, 'like', "%{$loweredQuery}%");
        };
    }

    public function filterFrom(string $field, bool $strict = true, ?string $filterName = null): self
    {
        $filterName = empty($filterName) ? 'from' : $filterName;
        $sign = $strict ? '>' : '>=';

        if (!empty($this->filter[$filterName])) {
            $this->addWhere($this->query, $field, $this->filter[$filterName], $sign);
        }

        return $this;
    }

    public function filterTo(string $field, bool $strict = true, ?string $filterName = null): self
    {
        $filterName = empty($filterName) ? 'to' : $filterName;
        $sign = $strict ? '<' : '<=';

        if (!empty($this->filter[$filterName])) {
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
}
