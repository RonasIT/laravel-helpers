<?php

namespace RonasIT\Support\Support;

use RonasIT\Support\Contracts\DBTypeResolverContract;

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
            self::BIGINT => [PHP_INT_MIN, PHP_INT_MAX],
            self::SMALLSERIAL => [1, 32767],
            self::SERIAL => [1, 2147483647],
            self::BIGSERIAL => [1, PHP_INT_MAX],
            self::REAL => [-3.4028234663852886e+38, 3.4028234663852886e+38],
            self::DOUBLE => [-PHP_FLOAT_MAX, PHP_FLOAT_MAX],
            self::VARCHAR => [0, 255],
            self::TEXT => [0, PHP_INT_MAX],
        ];
    }

    public const array NUMERIC_TYPES = [
        self::SMALLINT, self::INTEGER, self::BIGINT, self::SMALLSERIAL, self::SERIAL, self::BIGSERIAL,
        self::REAL, self::DOUBLE,
    ];

    public const array STRING_TYPES = [self::VARCHAR, self::TEXT];

    public function isNumeric(string $type): bool
    {
        return in_array($type, self::NUMERIC_TYPES, true);
    }

    public function isString(string $type): bool
    {
        return in_array($type, self::STRING_TYPES, true);
    }

    public function hasType(string $type): bool
    {
        return array_key_exists($type, self::ranges());
    }
}
