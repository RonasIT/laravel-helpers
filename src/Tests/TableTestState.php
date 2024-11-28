<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Str;

class TableTestState extends ModelTestState
{
    protected string $tableName;

    public function __construct(string $tableName, $namespace = 'App\Models\\')
    {
        $this->tableName = $tableName;

        $modelClassName = $this->getModelName($tableName);

        $class = $namespace . $modelClassName;

        parent::__construct($class);
    }

    function getModelName($string): string
    {
        $words = explode('_', $string);

        $pascalWords = array_map('ucfirst', $words);

        return Str::singular(implode('', $pascalWords));
    }
}
