<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckIpMiddleware
{
    public function handle(Request $request, Closure $next, string ...$allowedIps): Response
    {
        $ips = [$request->header('x-real-ip'), $request->header('x-forwarded-for')];

        if (empty(array_intersect($allowedIps, $ips)) && App::environment('production')) {
            throw new AccessDeniedHttpException();
        }

        return $next($request);
    }
}
