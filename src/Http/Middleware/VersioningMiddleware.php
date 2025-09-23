<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RonasIT\Support\Support\Version;
use Symfony\Component\HttpFoundation\Response;

class VersioningMiddleware
{
    public function handle(Request $request, Closure $next, $paramName = 'version')
    {
        $current = Version::current();

        if (in_array($current, config('app.disabled_api_versions'))) {
            abort(Response::HTTP_UPGRADE_REQUIRED);
        }

        $request->route()->forgetParameter($paramName);

        return $next($request);
    }
}
