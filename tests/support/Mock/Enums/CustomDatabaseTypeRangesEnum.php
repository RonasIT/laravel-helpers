<?php

namespace RonasIT\Support\Tests\Support\Mock\Enums;

use RonasIT\Support\Contracts\DatabaseTypeRangesContract;

enum CustomDatabaseTypeRangesEnum: string implements DatabaseTypeRangesContract
{
    case Integer = 'integer';

    public static function ranges(): array
    {
        return [
            self::Integer->value => [0, 100],
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
        return [];
    }
}
