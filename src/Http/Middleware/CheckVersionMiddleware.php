<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RonasIT\Support\Support\Version;
use Symfony\Component\HttpFoundation\Response;

class CheckVersionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $current = Version::current();

        if (in_array($current, config('app.disabled_api_versions', []))) {
            abort(Response::HTTP_UPGRADE_REQUIRED);
        }

        return $next($request);
    }
}
