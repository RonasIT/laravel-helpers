<?php

namespace RonasIT\Support\Support;

use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;

final class PostgresDBTypeResolver implements DBTypeResolverContract
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

    private const array CATEGORIES = [
        DBTypeCategoryEnum::Integer->value => [
            self::SMALLINT, self::INTEGER, self::SMALLSERIAL, self::SERIAL, self::BIGINT, self::BIGSERIAL,
        ],
        DBTypeCategoryEnum::Float->value => [
            self::REAL, self::DOUBLE,
        ],
        DBTypeCategoryEnum::String->value => [
            self::VARCHAR,
        ],
    ];

    private const array RANGES = [
        self::SMALLINT => [-32768, 32767],
        self::INTEGER => [-2147483648, 2147483647],
        self::BIGINT => ['-9223372036854775808', '9223372036854775807'],
        self::SMALLSERIAL => [1, 32767],
        self::SERIAL => [1, 2147483647],
        self::BIGSERIAL => ['1', '9223372036854775807'],
        self::REAL => [-3.4028234663852886e+38, 3.4028234663852886e+38],
        self::DOUBLE => [-PHP_FLOAT_MAX, PHP_FLOAT_MAX],
        self::VARCHAR => [0, 255],
    ];

    public function getRange(string $type): array
    {
        return self::RANGES[$type];
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::RANGES);
    }

    public function isTypeCategory(DBTypeCategoryEnum $category, string $type): bool
    {
        return in_array($type, self::CATEGORIES[$category->value], true);
    }
}
