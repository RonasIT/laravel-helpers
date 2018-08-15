<?php

namespace RonasIT\Support\Middleware;

use Symfony\Component\HttpFoundation\Response;
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
     */
    public function handle($request, Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', 400);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {

            return $this->refreshToken($request, $next);

        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }

        if (!$user) {
            return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
        }

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }

    /**
     * Refresh token
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
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