<?php

namespace RonasIT\Support\Support;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;

class Version
{
    public static function current(): string
    {
        $route = Route::getRoutes()->match(request());

        return $route->parameters()['version'] ?? str_replace('/v', '', $route->getPrefix());
    }

    public static function is(VersionEnumContract $checkedVersion): bool
    {
        return $checkedVersion->value === self::current();
    }

    public static function between(VersionEnumContract $from, VersionEnumContract $to): bool
    {
        $version = self::current();

        return $version >= $from->value && $version <= $to->value;
    }

    public static function from(VersionEnumContract $from): bool
    {
        return self::current() >= $from->value;
    }

    public static function to(VersionEnumContract $to): bool
    {
        return self::current() <= $to->value;
    }
}
