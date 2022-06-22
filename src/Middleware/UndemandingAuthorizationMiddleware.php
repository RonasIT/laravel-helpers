<?php

namespace RonasIT\Support\Middleware;

use Closure;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Middleware\BaseMiddleware;
use Illuminate\Contracts\Routing\ResponseFactory;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Illuminate\Contracts\Events\Dispatcher as DispatcherInterface;

/**
 * @deprecated
 */
class UndemandingAuthorizationMiddleware extends BaseMiddleware
{
    protected $auth;

    /**
     * @var DispatcherInterface
     */
    protected $events;

    public function __construct(ResponseFactory $response, Dispatcher $events, JWTAuth $auth)
    {
        parent::__construct($response, $events, $auth);

        $this->auth = app(JWTAuth::class);
        $this->events = app(Dispatcher::class);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            return $this->authenticate($request, $next);
        } catch (TokenExpiredException $e) {
            return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
        } catch (JWTException $e) {
            return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
        }
    }

    private function authenticate($request, $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $next($request);
        }

        $user = $this->auth->authenticate($token);

        if (!$user) {
            return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
        }

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }
}
