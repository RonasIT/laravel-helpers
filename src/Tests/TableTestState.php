<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Str;

class TableTestState extends ModelTestState
{
    protected string $tableName;

    public function __construct(string $tableName, string $namespace = 'App\Models\\')
    {
        $this->tableName = $tableName;

        $modelClassName = $this->getModelName($this->tableName);

        $class = $namespace . $modelClassName;

        parent::__construct($class);
    }

    protected function getModelName(string $tableName): string
    {
        $words = explode('_', $tableName);

        $pascalWords = array_map('ucfirst', $words);

        return Str::singular(implode('', $pascalWords));
    }
}
