<?php

namespace RonasIT\Support\Tests;;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RonasIT\Support\Http\Middleware\CheckIpMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckIpMiddlewareTest extends TestCase
{
    protected CheckIpMiddleware $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckIpMiddleware();
    }

    public function testHandleWithValidIpProdEnv(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $request = Request::create(
            uri: '/test',
            method: 'GET',
            server: ['HTTP_X_FORWARDED_FOR' => '127.0.0.2'],
        );

        $response = $this->middleware->handle($request, fn () => new Response(), '127.0.0.1', '127.0.0.2');

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandleWithInvalidIpProdEnv(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(AccessDeniedHttpException::class);

        $request = Request::create(
            uri: '/test',
            method: 'GET',
            server: ['HTTP_X_FORWARDED_FOR' => '127.0.0.3'],
        );

        $this->middleware->handle($request, fn () => new Response(), '127.0.0.1', '127.0.0.2');
    }

    public function testHandleWithInvalidIpNonProdEnv(): void
    {
        $request = Request::create(
            uri: '/test',
            method: 'GET',
            server: ['HTTP_X_FORWARDED_FOR' => '127.0.0.3'],
        );

        $response =$this->middleware->handle($request, fn () => new Response(), '127.0.0.1', '127.0.0.2');

        $this->assertInstanceOf(Response::class, $response);
    }
}
