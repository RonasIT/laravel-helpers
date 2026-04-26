<?php

namespace RonasIT\Support\Tests\Support\Mock\Resolvers;

use RonasIT\Support\Contracts\DBTypeResolverContract;

class TestDBTypeResolverWithUncategorizedTypes implements DBTypeResolverContract
{
    public const string DECIMAL = 'decimal';

    public static function ranges(): array
    {
        return [
            self::DECIMAL => [0, PHP_FLOAT_MAX],
        ];
    }

    public function isNumeric(string $type): bool
    {
        return false;
    }

    public function isString(string $type): bool
    {
        return false;
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::ranges());
    }
}
