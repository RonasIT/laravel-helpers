<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RonasIT\Support\Support\Version;
use Symfony\Component\HttpFoundation\Response;

class CheckVersionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $current = Str::replace('v', '', Version::current());

        if (in_array($current, config('app.disabled_api_versions'))) {
            abort(Response::HTTP_UPGRADE_REQUIRED);
        }

        return $next($request);
    }
}
