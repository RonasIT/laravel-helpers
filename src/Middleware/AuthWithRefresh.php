<?php

namespace RonasIT\Support\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Middleware\GetUserFromToken;

class AuthWithRefresh extends GetUserFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws JWTException
     * @throws JWTException
     */
    public function handle($request, Closure $next)
    {
        $response = null;

        try {
            $response = $this->authenticate($request, $next);
        } catch (TokenExpiredException $e) {
            $response = $this->refreshToken($request, $next);
        } catch (JWTException $e) {
            $response = $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }

        return $response;
    }

    private function authenticate($request, $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', 400);
        }

        $user = $this->auth->authenticate($token);

        if (!$user) {
            $response = $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
        } else {
            $this->events->fire('tymon.jwt.valid', $user);
            $response = $next($request);
        }

        return $response;
    }

    /**
     * Refresh token
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws JWTException
     */
    private function refreshToken($request, Closure $next)
    {

        try {
            $newToken = $this->auth->setRequest($request)->parseToken()->refresh();
        } catch (TokenExpiredException $e) {
            return $this->respond('tymon.jwt.expired', 'token_expired_refresh_ttl', $e->getStatusCode(), [$e]);
        }

        $response = $next($request);

        $response->headers->add(['New-Token' => $newToken]);

        return $response;
    }
}