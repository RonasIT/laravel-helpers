<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

class TestModelWithDependentAccessors extends TestModel
{
    public function getNameAttribute(?string $value): string
    {
        return "{$value} {$this->surname}";
    }

    public function getTitleAttribute(?string $value): string
    {
        return "{$value} ({$this->relation->count()})";
    }
}
