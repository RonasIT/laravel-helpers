<?php

namespace RonasIT\Support\Tests\Support\Mock\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class TestModelWithDependentAccessors extends TestModel
{
    protected string $currency = 'USD';

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPriceAttribute(?string $value): string
    {
        return "{$value} {$this->currency}";
    }

    public function getNameAttribute(?string $value): string
    {
        return "{$value} {$this->surname}";
    }

    public function getTitleAttribute(?string $value): string
    {
        return "{$value} ({$this->relation->count()})";
    }

    protected function meta(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => (object) ['value' => $value],
        );
    }
}
