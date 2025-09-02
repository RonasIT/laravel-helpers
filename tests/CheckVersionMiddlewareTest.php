<?php

namespace RonasIT\Support\Tests;;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Http\Middleware\CheckVersionMiddleware;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckVersionMiddlewareTest extends TestCase
{
    use RouteMockTrait;

    protected CheckVersionMiddleware $middleware;

    protected const ROUTE_OBJECT_RANGE = '/test-object-range';

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckVersionMiddleware();

        $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);
    }

    public function testHandleDisabledApiVersion(): void
    {
        $this->mockRouteObjectRange();

        Config::set('app.disabled_api_versions', [1]);

        $this->expectException(HttpException::class);

        $this->app->request = Request::create('v1/test-object-range', 'get');

        $this->middleware->handle($this->app->request, function () {});
    }

    public function testHandleEnabledApiVersion(): void
    {
        $this->mockRouteObjectRange();

        $this->app->request = Request::create('v1/test-object-range', 'get');

        $response = $this->middleware->handle($this->app->request, fn () => new Response());

        $this->assertInstanceOf(Response::class, $response);
    }
}
