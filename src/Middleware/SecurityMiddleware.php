<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 23.03.17
 * Time: 18:04
 */

namespace RonasIT\Support\Middleware;

use Closure;
use Illuminate\Cache\Repository as Cache;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function handle($request, Closure $next)
    {
        if ($this->needToLock($request)) {
            $this->cache->forever('order_66_activated', true);
        }

        if ($this->needToUnlock($request)) {
            $this->cache->forget('order_66_activated');
        }

        if ($this->cache->get('order_66_activated')) {
            return response(
                view('errors.503')->render(),
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $next($request);
    }

    protected function needToLock($request) {
        return (
            ($request->header('Order66') == 'activate') &&
            ($request->header('App-Key') == config('app.key'))
        );
    }

    protected function needToUnlock($request) {
        return (
            ($request->header('Order66') == 'deactivate') &&
            ($request->header('App-Key') == config('app.key'))
        );
    }
}