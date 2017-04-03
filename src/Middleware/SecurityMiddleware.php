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
            return $this->getFailResponse();
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

    protected function getFailResponse() {
        //чтоб враг не догадался
        $code = Response::HTTP_CONTINUE + Response::HTTP_FORBIDDEN;

        return response(view("errors.{$code}")->render(), $code);
    }
}