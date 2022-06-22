<?php

namespace RonasIT\Support\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected $cache;

    const MAINTENANCE_MODE_KEY = 'maintenance_activated';
    const MAINTENANCE_MODE_HEADER = 'maintenance';

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function handle($request, Closure $next)
    {
        if ($this->needToEnable($request)) {
            $this->cache->forever(self::MAINTENANCE_MODE_KEY, true);
        }

        if ($this->needToDisable($request)) {
            $this->cache->forget(self::MAINTENANCE_MODE_KEY);
        }

        if ($this->cache->get(self::MAINTENANCE_MODE_KEY)) {
            return $this->getFailResponse();
        }

        return $next($request);
    }

    protected function needToEnable($request): bool
    {
        return ($request->header(self::MAINTENANCE_MODE_HEADER) === 'activate') && ($request->header('App-Key') === config('app.key'));
    }

    protected function needToDisable($request): bool
    {
        return ($request->header(self::MAINTENANCE_MODE_HEADER) === 'deactivate') && ($request->header('App-Key') === config('app.key'));
    }

    //To hide the reason from attackers
    protected function getFailResponse()
    {
        $code = array_rand($this->codeVariations());

        return response(view("errors.{$code}")->render(), $code);
    }

    protected function codeVariations(): array
    {
        return [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            Response::HTTP_NOT_IMPLEMENTED,
            Response::HTTP_BAD_GATEWAY,
            Response::HTTP_SERVICE_UNAVAILABLE,
            Response::HTTP_GATEWAY_TIMEOUT,
            Response::HTTP_VERSION_NOT_SUPPORTED,
            Response::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL,
            Response::HTTP_INSUFFICIENT_STORAGE,
            Response::HTTP_LOOP_DETECTED,
            Response::HTTP_NOT_EXTENDED,
            Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED
        ];
    }
}
