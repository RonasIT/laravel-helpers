<?php

namespace RonasIT\Support\Traits;

trait EnumTrait
{
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, self::cases());
    }

    public static function toString(string $separator = ','): string
    {
        return implode($separator, self::values());
    }
}
