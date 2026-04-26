<?php

namespace RonasIT\Support\Tests\Support\Mock\Resolvers;

use RonasIT\Support\Contracts\DBTypeResolverContract;

class TestDBTypeResolver implements DBTypeResolverContract
{
    public const string INTEGER = 'integer';
    public const string STRING = 'string';

    public const int INTEGER_MIN = 0;
    public const int INTEGER_MAX = 32767;
    public const int STRING_MIN = 0;
    public const int STRING_MAX = 128;

    public static function ranges(): array
    {
        return [
            self::INTEGER => [self::INTEGER_MIN, self::INTEGER_MAX],
            self::STRING => [self::STRING_MIN, self::STRING_MAX],
        ];
    }

    public function isNumeric(string $type): bool
    {
        return $type === self::INTEGER;
    }

    public function isString(string $type): bool
    {
        return $type === self::STRING;
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::ranges());
    }
}
