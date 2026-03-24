<?php

namespace RonasIT\Support\Tests\Support\Mock\Enums;

use RonasIT\Support\Contracts\DatabaseTypeRangesContract;

enum DatabaseTypeRangesWithUncategorizedTypesEnum: string implements DatabaseTypeRangesContract
{
    case Decimal = 'decimal';
    case Varchar = 'varchar';
    case Integer = 'integer';

    public static function ranges(): array
    {
        return [
            self::Decimal->value => [0, PHP_INT_MAX],
        ];
    }

    public static function integerTypes(): array
    {
        return [
            self::Integer->value,
        ];
    }

    public static function stringTypes(): array
    {
        return [
            self::Varchar->value,
        ];
    }
}
