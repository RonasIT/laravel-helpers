<?php

namespace RonasIT\Support\Tests\Support\Enum;

use RonasIT\Support\Contracts\VersionEnumContract;

// Backward compatibility with PHP < 8
class VersionEnum implements VersionEnumContract
{
    public const v1 = '1';
    public const v2 = '2';
    public const v3 = '3';

    public static function values(): array
    {
        return [static::v1, static::v2, static::v3];
    }

    public static function toString(string $separator = ','): string
    {
        return implode($separator, self::values());
    }
}
