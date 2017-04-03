<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 11:59
 */

namespace RonasIT\Support\Repositories;

use Illuminate\Support\Facades\DB;
use RonasIT\Support\Traits\EntityControlTrait;

class BaseRepository
{
    use EntityControlTrait;

    protected $isImport = false;
    protected $filter;
    protected $query;

    public function importMode($mode = true)
    {
        $this->isImport = $mode;

        return $this;
    }

    public function isImportMode()
    {
        return $this->isImport;
    }

    public function paginate($query, $data = [])
    {
        $defaultPerPage = config('defaults.items_per_page');

        return $query->paginate($data['per_page'] ?? $defaultPerPage);
    }

    public function forceDelete($where)
    {
        $this->getQuery()->where($where)->forceDelete();
    }

    protected function filterBy($field)
    {
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
                    $loweredQuery = strtolower($this->filter['query']);
                    $field = DB::raw("lower({$field})");

                    $query->orWhere($field, 'like', "%{$loweredQuery}%");
                }
            });
        }

        return $this;
    }

    protected function searchQuery($filter)
    {
        $this->query = $this->getQuery()
            ->with(['role', 'title']);

        $this->filter = $filter;

        if (!empty($filter['with_trashed'])) {
            $this->query->withTrashed();
        }

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
}