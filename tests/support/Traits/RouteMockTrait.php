<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Routing\RouteFacade;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;

trait RouteMockTrait
{
    protected function mockRoutes()
    {
        $this->mockRouteFacadeRange();
        $this->mockRouteFacadeFrom();
        $this->mockRouteFacadeTo();

        $this->mockRouteObjectRange();
        $this->mockRouteObjectFrom();
        $this->mockRouteObjectTo();

        $this->mockRouteFacadeVersion();
    }

    protected function mockRouteFacadeRange(): void
    {
        $versionFrom = $this->createMock(VersionEnumContract::class);
        $versionFrom->value = VersionEnum::v1;
        $versionTo = $this->createMock(VersionEnumContract::class);
        $versionTo->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionTo, $versionFrom) {
            RouteFacade::versionRange($versionFrom, $versionTo)->group(function () {
                Route::get(static::ROUTE_FACADE_RANGE, function () {
                    return 'ROUTE_FACADE_RANGE';
                });
            });
        });
    }

    protected function mockRouteFacadeFrom(): void
    {
        $versionFrom = $this->createMock(VersionEnumContract::class);
        $versionFrom->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionFrom) {
            RouteFacade::versionFrom($versionFrom)->group(function () {
                Route::get(static::ROUTE_FACADE_FROM, function () {
                    return 'ROUTE_FACADE_FROM';
                });
            });
        });
    }

    protected function mockRouteFacadeTo(): void
    {
        $versionTo = $this->createMock(VersionEnumContract::class);
        $versionTo->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionTo) {
            RouteFacade::versionTo($versionTo)->group(function () {
                Route::get(static::ROUTE_FACADE_TO, function () {
                    return 'ROUTE_FACADE_TO';
                });
            });
        });
    }

    protected function mockRouteObjectRange(): void
    {
        $versionFrom = $this->createMock(VersionEnumContract::class);
        $versionFrom->value = VersionEnum::v1;
        $versionTo = $this->createMock(VersionEnumContract::class);
        $versionTo->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionTo, $versionFrom) {
            Route::get(static::ROUTE_OBJECT_RANGE, function () {
                return 'ROUTE_OBJECT_RANGE';
            })->versionRange($versionFrom, $versionTo);
        });
    }

    protected function mockRouteObjectFrom(): void
    {
        $versionFrom = $this->createMock(VersionEnumContract::class);
        $versionFrom->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionFrom) {
            Route::get(static::ROUTE_OBJECT_FROM, function () {
                return 'ROUTE_OBJECT_FROM';
            })->versionFrom($versionFrom);
        });
    }

    protected function mockRouteObjectTo(): void
    {
        $versionTo = $this->createMock(VersionEnumContract::class);
        $versionTo->value = VersionEnum::v2;

        Route::group(['prefix' => 'v{version}'], function () use ($versionTo) {
            Route::get(static::ROUTE_OBJECT_TO, function () {
                return 'ROUTE_OBJECT_TO';
            })->versionTo($versionTo);
        });
    }

    protected function mockRouteFacadeVersion(): void
    {
        $version = $this->createMock(VersionEnumContract::class);
        $version->value = VersionEnum::v2;

        RouteFacade::version($version)->group(function () use ($version) {
            Route::get(static::ROUTE_FACADE_VERSION, function () {
                return 'ROUTE_FACADE_VERSION';
            });
        });
    }
}