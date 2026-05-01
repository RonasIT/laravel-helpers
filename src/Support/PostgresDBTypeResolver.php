<?php

namespace RonasIT\Support\Support;

use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;

class PostgresDBTypeResolver implements DBTypeResolverContract
{
    public const string SMALLINT = 'smallint';
    public const string INTEGER = 'integer';
    public const string BIGINT = 'bigint';
    public const string SMALLSERIAL = 'smallserial';
    public const string SERIAL = 'serial';
    public const string BIGSERIAL = 'bigserial';
    public const string REAL = 'real';
    public const string DOUBLE = 'double';
    public const string VARCHAR = 'varchar';
    public const string TEXT = 'text';

    public static function ranges(): array
    {
        return [
            self::SMALLINT => [-32768, 32767],
            self::INTEGER => [-2147483648, 2147483647],
            self::BIGINT => [(string) PHP_INT_MIN, (string) PHP_INT_MAX],
            self::SMALLSERIAL => [1, 32767],
            self::SERIAL => [1, 2147483647],
            self::BIGSERIAL => ['1', (string) PHP_INT_MAX],
            self::REAL => [-3.4028234663852886e+38, 3.4028234663852886e+38],
            self::DOUBLE => [-PHP_FLOAT_MAX, PHP_FLOAT_MAX],
            self::VARCHAR => [0, 255],
            self::TEXT => [0, INF],
        ];
    }

    public const array CATEGORIES = [
        DBTypeCategoryEnum::Integer->value => [
            self::SMALLINT, self::INTEGER, self::SMALLSERIAL, self::SERIAL,
        ],
        DBTypeCategoryEnum::BigInteger->value => [
            self::BIGINT, self::BIGSERIAL,
        ],
        DBTypeCategoryEnum::Float->value => [
            self::REAL, self::DOUBLE,
        ],
        DBTypeCategoryEnum::String->value => [
            self::VARCHAR, self::TEXT,
        ],
    ];

    public function isTypeCategory(DBTypeCategoryEnum $category, string $type): bool
    {
        return in_array($type, self::CATEGORIES[$category->value] ?? [], true);
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::ranges());
    }
}
