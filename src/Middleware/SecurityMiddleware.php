<?php

namespace RonasIT\Support\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected $cache;
    const ORDER = 'order_activated';

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function handle($request, Closure $next)
    {
        if ($this->needToLock($request)) {
            $this->cache->forever(self::ORDER, true);
        }

        if ($this->needToUnlock($request)) {
            $this->cache->forget(self::ORDER);
        }

        if ($this->cache->get(self::ORDER)) {
            return $this->getFailResponse();
        }

        return $next($request);
    }

    protected function needToLock($request): bool
    {
        return ($request->header('Order') === 'activate') && ($request->header('App-Key') === config('app.key'));
    }

    protected function needToUnlock($request): bool
    {
        return ($request->header('Order') === 'deactivate') && ($request->header('App-Key') === config('app.key'));
    }

    // чтоб ddoser не догадался
    protected function getFailResponse()
    {
        $code = Response::HTTP_SERVICE_UNAVAILABLE;

        return response(view("errors.{$code}")->render(), $code);
    }
}
