<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Constraint\LogicalNot;
use RonasIT\Support\Testing\TestCase;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use Symfony\Component\HttpFoundation\Response;

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

    protected function getMockPackageTestCaseCall(): TestCase
    {
        return $this
            ->getMockBuilder(TestCase::class)
            ->onlyMethods(['call'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function assertRouteCalled(TestCase $mock, string $uri, string $method = 'get'): void
    {
        $mock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo($method),
                $this->equalTo($uri)
            )
            ->willReturn(TestResponse::fromBaseResponse('', Response::HTTP_OK));
    }

    protected function assertRouteNotCalled(TestCase $mock, string $uri, string $method = 'get'): void
    {
        $mock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo($method),
                new LogicalNot($this->equalTo($uri))
            )
            ->willReturn(TestResponse::fromBaseResponse('', Response::HTTP_NOT_FOUND));
    }
}
