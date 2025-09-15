<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Http\Middleware\VersioningMiddleware;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VersioningMiddlewareTest extends TestCase
{
    use RouteMockTrait;

    protected VersioningMiddleware $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = new VersioningMiddleware();

        $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);
    }

    public function testHandleDisabledAPIVersion(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', [1]);

        $this->assertExceptionThrew(HttpException::class, '');

        $request = Request::create('v1/test-object-range', 'get');

        $this->app->bind('request', fn () => $request);

        $this->middleware->handle($request, function () {});
    }

    public function testHandleEnabledAPIVersion(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', []);

        $request = Request::create('v1/test-object-range', 'get');

        $this->app->bind('request', fn () => $request);

        $this->mockRoute('GET', 'v{version}/test-object-range');

        $response = $this->middleware->handle($request, fn () => new Response());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCheckVersionParameterRemoved(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', []);

        $request = Request::create('v1/test-object-range', 'get');

        $this->app->bind('request', fn () => $request);

        $route = $this->mockRoute('GET', 'v{version}/test-object-range');

        $this->middleware->handle($request, fn () => new Response());

        $this->assertNull($route->parameter('version'));
    }

    protected function mockRoute(array|string $methods, string $uri): Route
    {
        $route = new Route(Arr::wrap($methods), $uri, function () {});

        $route->bind($this->app->request);

        $route->setParameter('version', 1);

        $this->app->request->setRouteResolver(fn () => $route);

        return $route;
    }
}
