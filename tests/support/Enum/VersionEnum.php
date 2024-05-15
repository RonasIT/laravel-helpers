<?php

namespace RonasIT\Support\Tests\Support\Enum;

use RonasIT\Support\Contracts\VersionEnumContract;

// Backward compatibility with PHP < 8
class VersionEnum implements VersionEnumContract
{
    const v1 = '1';
    const v2 = '2';
    const v11 = '11';
    const v12 = '12';

    public static function values(): array
    {
        return [static::v1, static::v2, static::v11, static::v12];
    }

    public static function toString(string $separator = ','): string
    {
        return implode($separator, self::values());
    }
}