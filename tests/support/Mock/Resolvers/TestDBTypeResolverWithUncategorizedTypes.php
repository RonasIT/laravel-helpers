<?php

namespace RonasIT\Support\Tests\Support\Mock\Resolvers;

use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;

class TestDBTypeResolverWithUncategorizedTypes implements DBTypeResolverContract
{
    public const string DECIMAL = 'decimal';

    private const array RANGES = [
        self::DECIMAL => [0, PHP_FLOAT_MAX],
    ];

    public function getRange(string $type): array
    {
        return self::RANGES[$type];
    }

    public function isTypeCategory(DBTypeCategoryEnum $category, string $type): bool
    {
        return false;
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::RANGES);
    }
}
