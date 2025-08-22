<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearVersion
{
    public function handle(Request $request, Closure $next, $paramName = 'version'): mixed
    {
        $request->route()->forgetParameter($paramName);

        return $next($request);
    }
}
