<?php

namespace RonasIT\Support\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;

class Version
{
    public static function current($pathParamName = 'version'): string
    {
        $route = Route::getRoutes()->match(request());

        return Arr::get($route->parameters(), $pathParamName, str_replace('/v', '', $route->getPrefix()));
    }

    public static function is(VersionEnumContract $expectedVersion): bool
    {
        return version_compare($expectedVersion->value, self::current(), '=');
    }

    public static function between(VersionEnumContract $from, VersionEnumContract $to): bool
    {
        $version = self::current();

        return version_compare($version, $from->value, '>=') && version_compare($version, $to->value, '<=');
    }

    public static function gte(VersionEnumContract $from): bool
    {
        return version_compare(self::current(), $from->value, '>=');
    }

    public static function lte(VersionEnumContract $to): bool
    {
        return version_compare(self::current(), $to->value, '<=');
    }
}
