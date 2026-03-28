<?php

namespace RonasIT\Support\Enums;

use RonasIT\Support\Contracts\DatabaseTypeRangesContract;

enum PostgresDatabaseTypeEnum: string implements DatabaseTypeRangesContract
{
    case SmallInt = 'smallint';
    case Integer = 'integer';
    case BigInt = 'bigint';
    case SmallSerial = 'smallserial';
    case Serial = 'serial';
    case BigSerial = 'bigserial';
    case Varchar = 'varchar';
    case Text = 'text';

    public function range(): array
    {
        return match ($this) {
            self::SmallInt => [-32768, 32767],
            self::Integer => [-2147483648, 2147483647],
            self::BigInt => [PHP_INT_MIN, PHP_INT_MAX],
            self::SmallSerial => [1, 32767],
            self::Serial => [1, 2147483647],
            self::BigSerial => [1, PHP_INT_MAX],
            self::Varchar => [0, 255],
            self::Text => [0, PHP_INT_MAX],
        };
    }

    public static function integerTypes(): array
    {
        return [
            self::SmallInt->value,
            self::Integer->value,
            self::BigInt->value,
            self::SmallSerial->value,
            self::Serial->value,
            self::BigSerial->value,
        ];
    }

    public static function stringTypes(): array
    {
        return [
            self::Varchar->value,
            self::Text->value,
        ];
    }

    public static function ranges(): array
    {
        $ranges = [];

        foreach (self::cases() as $type) {
            $ranges[$type->value] = $type->range();
        }

        return $ranges;
    }
}
