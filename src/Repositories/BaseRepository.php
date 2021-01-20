<?php

namespace RonasIT\Support\Repositories;

use RonasIT\Support\Traits\EntityControlTrait;

class BaseRepository
{
    use EntityControlTrait;

    protected $isImport = false;
    protected $visibleAttributes = [];
    protected $hiddenAttributes = [];

    public function importMode($mode = true)
    {
        $this->isImport = $mode;

        return $this;
    }

    public function isImportMode()
    {
        return $this->isImport;
    }

    public function makeHidden($hiddenAttributes = [])
    {
        $this->hiddenAttributes = $hiddenAttributes;

        return $this;
    }

    public function makeVisible($visibleAttributes = [])
    {
        $this->visibleAttributes = $visibleAttributes;

        return $this;
    }
}