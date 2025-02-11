<?php

namespace RonasIT\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RemovePathParamMiddleware
{
    public function handle(Request $request, Closure $next, $paramName = 'version')
    {
        $request->route()->forgetParameter($paramName);

        return $next($request);
    }
}
