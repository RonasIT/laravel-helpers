<?php

namespace RonasIT\Support\Tests\Support\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Testing\TestResponse;
use RonasIT\Support\Testing\TestCase;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Routing\Route;

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
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::versionRange(VersionEnum::V1, VersionEnum::V2)->group(function () use ($route) {
                RouteFacade::get($route, function () {
                    return 'ROUTE_FACADE_RANGE';
                });
            });
        });
    }

    protected function mockRouteFacadeFrom(string $route = '/test-facade-from'): void
    {
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::versionFrom(VersionEnum::V2)->group(function () use ($route) {
                RouteFacade::get($route, function () {
                    return 'ROUTE_FACADE_FROM';
                });
            });
        });
    }

    protected function mockRouteFacadeTo(string $route = '/test-facade-to'): void
    {
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::versionTo(VersionEnum::V2)->group(function () use ($route) {
                RouteFacade::get($route, function () {
                    return 'ROUTE_FACADE_TO';
                });
            });
        });
    }

    protected function mockRouteObjectRange(string $route = '/test-object-range'): void
    {
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::get($route, function () {
                return 'ROUTE_OBJECT_RANGE';
            })->versionRange(VersionEnum::V1, VersionEnum::V2);
        });
    }

    protected function mockRouteObjectFrom(string $route = '/test-object-from'): void
    {
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::get($route, function () {
                return 'ROUTE_OBJECT_FROM';
            })->versionFrom(VersionEnum::V2);
        });
    }

    protected function mockRouteObjectTo(string $route = '/test-object-to'): void
    {
        RouteFacade::group(['prefix' => 'v{version}'], function () use ($route) {
            RouteFacade::get($route, function () {
                return 'ROUTE_OBJECT_TO';
            })->versionTo(VersionEnum::V2);
        });
    }

    protected function mockRouteFacadeVersion(string $route = '/test-facade-version'): void
    {
        RouteFacade::version(VersionEnum::V2)->group(function () use ($route) {
            RouteFacade::get($route, function () {
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

    protected function mockRouteObjectRangeWithBoundRequest(string $route, Request $request, int $version): Route
    {
        $this->mockRouteObjectRange($route);

        $route = $this->app->routes->get('GET')["v{version}{$route}"];

        $route->bind($request)->setParameter('version', $version);

        $request->setRouteResolver(fn () => $route);

        return $route;
    }
}
