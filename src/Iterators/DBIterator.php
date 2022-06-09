<?php

namespace RonasIT\Support\Iterators;

use Iterator;

class DBIterator implements Iterator
{
    protected $query;
    protected $position;
    protected $sample = [];
    protected $itemsPerPage;
    protected $currentItem = [];

    public function __construct($query, $itemsPerPage = 100)
    {
        $this->query = $query;
        $this->itemsPerPage = $itemsPerPage;
    }

    public function rewind()
    {
        $this->loadSample();
    }

    public function current()
    {
        return $this->sample['data'][$this->position];
    }

    public function key()
    {
        return $this->sample['data'][$this->position]['id'];
    }

    public function next()
    {
        $this->position++;

        if ($this->isEndOfSample()) {
            $this->loadSample($this->sample['current_page'] + 1);
        }
    }

    public function valid()
    {
        return !($this->isLastSample() && $this->isEndOfSample());
    }

    public function isEndOfSample()
    {
        return $this->position >= count($this->sample['data']);
    }

    public function isLastSample()
    {
        return $this->sample['current_page'] >= $this->sample['last_page'];
    }

    public function loadSample($page = 1)
    {
        $this->sample = $this->query
            ->paginate($this->itemsPerPage, ['*'], 'page', $page)
            ->toArray();

        $this->position = 0;
    }

    public function getGenerator()
    {
        $this->rewind();

        while ($this->valid()) {
            yield $this->current();

            $this->next();
        }
    }
}
