<?php

namespace RonasIT\Support\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Middleware\GetUserFromToken;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthWithRefresh extends GetUserFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     * @throws JWTException
     * @throws JWTException
     */
    public function handle($request, Closure $next)
    {
        try {
            return $this->authenticate($request, $next);
        } catch (TokenExpiredException $e) {
            return $this->refreshToken($request, $next);
        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }
    }

    private function authenticate($request, $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->auth->authenticate($token);

        if (!$user) {
            return $this->respond('tymon.jwt.user_not_found', 'user_not_found', Response::HTTP_NOT_FOUND);
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
