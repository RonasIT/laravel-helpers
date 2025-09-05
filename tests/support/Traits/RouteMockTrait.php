<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
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

    protected function mockRouteFacadeRange(string $route = '/test-facade-range'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::versionRange(VersionEnum::V1, VersionEnum::V2)->group(function () use ($route) {
                Route::get($route, function () {
                    return 'ROUTE_FACADE_RANGE';
                });
            });
        });
    }

    protected function mockRouteFacadeFrom(string $route = '/test-facade-from'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::versionFrom(VersionEnum::V2)->group(function () use ($route) {
                Route::get($route, function () {
                    return 'ROUTE_FACADE_FROM';
                });
            });
        });
    }

    protected function mockRouteFacadeTo(string $route = '/test-facade-to'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::versionTo(VersionEnum::V2)->group(function () use ($route) {
                Route::get($route, function () {
                    return 'ROUTE_FACADE_TO';
                });
            });
        });
    }

    protected function mockRouteObjectRange(string $route = '/test-object-range'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::get($route, function () {
                return 'ROUTE_OBJECT_RANGE';
            })->versionRange(VersionEnum::V1, VersionEnum::V2);
        });
    }

    protected function mockRouteObjectFrom(string $route = '/test-object-from'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::get($route, function () {
                return 'ROUTE_OBJECT_FROM';
            })->versionFrom(VersionEnum::V2);
        });
    }

    protected function mockRouteObjectTo(string $route = '/test-object-to'): void
    {
        Route::group(['prefix' => 'v{version}'], function () use ($route) {
            Route::get($route, function () {
                return 'ROUTE_OBJECT_TO';
            })->versionTo(VersionEnum::V2);
        });
    }

    protected function mockRouteFacadeVersion(string $route = '/test-facade-version'): void
    {
        Route::version(VersionEnum::V2)->group(function () use ($route) {
            Route::get($route, function () {
                return 'ROUTE_FACADE_VERSION';
            });
        });
    }

    protected function mockTestCaseExpectCallMethod(string $uri, string $method = 'get'): TestCase
    {
        $mock = $this
            ->getMockBuilder(TestCase::class)
            ->onlyMethods(['call'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo($method),
                $this->equalTo($uri)
            )
            ->willReturn(TestResponse::fromBaseResponse('', Response::HTTP_OK));

        return $mock;
    }
}
