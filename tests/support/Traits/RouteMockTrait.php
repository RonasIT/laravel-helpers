<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;
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
        Route::group(['prefix' => 'v{version}'], function () {
            Route::versionRange(VersionEnum::V1, VersionEnum::V2)->group(function () {
                Route::get(static::ROUTE_FACADE_RANGE, function () {
                    return 'ROUTE_FACADE_RANGE';
                });
            });
        });
    }

    protected function mockRouteFacadeFrom(): void
    {
        Route::group(['prefix' => 'v{version}'], function () {
            Route::versionFrom(VersionEnum::V2)->group(function () {
                Route::get(static::ROUTE_FACADE_FROM, function () {
                    return 'ROUTE_FACADE_FROM';
                });
            });
        });
    }

    protected function mockRouteFacadeTo(): void
    {
        Route::group(['prefix' => 'v{version}'], function () {
            Route::versionTo(VersionEnum::V2)->group(function () {
                Route::get(static::ROUTE_FACADE_TO, function () {
                    return 'ROUTE_FACADE_TO';
                });
            });
        });
    }

    protected function mockRouteObjectRange(): void
    {
        Route::group(['prefix' => 'v{version}'], function () {
            Route::get(static::ROUTE_OBJECT_RANGE, function () {
                return 'ROUTE_OBJECT_RANGE';
            })->versionRange(VersionEnum::V1, VersionEnum::V2);
        });
    }

    protected function mockRouteObjectFrom(): void
    {
        Route::group(['prefix' => 'v{version}'], function () {
            Route::get(static::ROUTE_OBJECT_FROM, function () {
                return 'ROUTE_OBJECT_FROM';
            })->versionFrom(VersionEnum::V2);
        });
    }

    protected function mockRouteObjectTo(): void
    {
        Route::group(['prefix' => 'v{version}'], function () {
            Route::get(static::ROUTE_OBJECT_TO, function () {
                return 'ROUTE_OBJECT_TO';
            })->versionTo(VersionEnum::V2);
        });
    }

    protected function mockRouteFacadeVersion(): void
    {
        Route::version(VersionEnum::V2)->group(function () {
            Route::get(static::ROUTE_FACADE_VERSION, function () {
                return 'ROUTE_FACADE_VERSION';
            });
        });
    }
}
