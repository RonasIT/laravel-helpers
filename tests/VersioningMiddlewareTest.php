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

    public function testHandleDisabledApiVersion(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', [1]);

        $this->assertExceptionThrew(HttpException::class, '');

        $request = Request::create('v1/test-object-range', 'get');

        $this->app->bind('request', fn () => $request);

        $this->middleware->handle($request, function () {});
    }

    public function testHandleEnabledApiVersion(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', []);

        $request = Request::create('v1/test-object-range', 'get');

        $this->app->bind('request', fn () => $request);

        $route = new Route(['GET'], 'v{version}/test-object-range', function () {});
        $route->bind($request);
        $route->setParameter('version', 1);

        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn () => new Response());

        $this->assertInstanceOf(Response::class, $response);

        $this->assertNull($route->parameter('version'));
    }
}
