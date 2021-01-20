<?php

namespace RonasIT\Support\Repositories;

use RonasIT\Support\Traits\EntityControlTrait;

class BaseRepository
{
    use EntityControlTrait;

    protected $isImport = false;

    public function importMode($mode = true)
    {
        $this->isImport = $mode;

        return $this;
    }

    public function isImportMode()
    {
        return $this->isImport;
    }
}