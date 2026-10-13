<?php

namespace RonasIT\Support\Tests\Support\Mock\Resolvers;

use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;

class TestDBTypeResolver implements DBTypeResolverContract
{
    public const string INTEGER = 'integer';
    public const string STRING = 'string';
    public const string UNCATEGORIZED = 'uncategorized';

    public const int INTEGER_MIN = 0;
    public const int INTEGER_MAX = 32767;
    public const int STRING_MIN = 0;
    public const int STRING_MAX = 128;

    private const array RANGES = [
        self::INTEGER => [self::INTEGER_MIN, self::INTEGER_MAX],
        self::STRING => [self::STRING_MIN, self::STRING_MAX],
        self::UNCATEGORIZED => [0, PHP_FLOAT_MAX],
    ];

    public function getRange(string $type): array
    {
        return self::RANGES[$type];
    }

    public function getTypes(): array
    {
        return array_keys(self::RANGES);
    }

    public function getTypeCategory(string $type): ?DBTypeCategoryEnum
    {
        return match ($type) {
            self::INTEGER => DBTypeCategoryEnum::Integer,
            self::STRING => DBTypeCategoryEnum::String,
            default => null,
        };
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::RANGES);
    }
}
