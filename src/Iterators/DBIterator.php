<?php

namespace RonasIT\Support\Iterators;

use Iterator;
use Generator;

class DBIterator implements Iterator
{
    protected $currentItem = [];
    protected $sample = [];
    protected $query;
    protected $itemsPerPage;
    protected $position;

    public function __construct($query, $itemsPerPage = 100)
    {
        $this->query = $query;
        $this->itemsPerPage = $itemsPerPage;
    }

    public function rewind(): void
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

    public function next(): void
    {
        $this->position++;

        if ($this->isEndOfSample()) {
            $this->loadSample($this->sample['current_page'] + 1);
        }
    }

    public function valid(): bool
    {
        return !($this->isLastSample() && $this->isEndOfSample());
    }

    public function isEndOfSample(): bool
    {
        return $this->position >= count($this->sample['data']);
    }

    public function isLastSample(): bool
    {
        return $this->sample['current_page'] >= $this->sample['last_page'];
    }

    public function loadSample($page = 1): void
    {
        $this->sample = $this->query
            ->paginate($this->itemsPerPage, ['*'], 'page', $page)
            ->toArray();

        $this->position = 0;
    }

    public function getGenerator(): Generator
    {
        $this->rewind();

        while ($this->valid()) {
            yield $this->current();

            $this->next();
        }
    }
}
