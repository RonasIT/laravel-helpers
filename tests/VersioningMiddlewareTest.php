<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Response;
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

        $request = $this->createRequestObject('v1/test-object-range', 'get');

        $this->middleware->handle($request, function () {});
    }

    public function testHandleEnabledAPIVersion(): void
    {
        Config::set('app.disabled_api_versions', []);

        $request = $this->createRequestObject('v1/test-object-range', 'get');

        $this->mockBoundObjectRangeRoute('/test-object-range', 1);

        $response = $this->middleware->handle($request, fn () => new Response());

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCheckVersionParameterRemoved(): void
    {
        Config::set('app.disabled_api_versions', []);

        $request = $this->createRequestObject('v1/test-object-range', 'get');

        $route = $this->mockBoundObjectRangeRoute('/test-object-range', 1);

        $this->middleware->handle($request, fn () => new Response());

        $this->assertNull($route->parameter('version'));
    }
}
