<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 8:37
 */

namespace RonasIT\Support;

use Illuminate\Support\ServiceProvider;
use RonasIT\Support\Commands\MakeEntityCommand;
use RonasIT\Support\Middleware\SecurityMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\JWTAuth;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot() {
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        Validator::extend('unique_except_of_current_user', function ($attribute, $value, $parameters, $validator) {
            $userId = app(JWTAuth::class)->toUser()->id;
            $result = DB::select("select count(*) from users where id <> {$userId} and {$attribute} = '{$value}';");

            return $result[0]->count == 0;
        });
    }

    public function register()
    {

    }
}