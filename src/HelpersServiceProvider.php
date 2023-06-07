<?php

namespace RonasIT\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\ExcelServiceProvider;
use RonasIT\Support\Middleware\SecurityMiddleware;
use Illuminate\Support\Arr;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        Validator::extend('unique_except_of_authorized_user', function ($attribute, $value, $parameters = []) {
            $table = !empty($parameters)
                ? $parameters[0]
                : 'users';
            $keyField = (!empty($parameters) && count($parameters) > 1)
                ? $parameters[1]
                : 'id';
            $userId = Auth::id();

            $result = DB::table($table)
                ->where($keyField, '<>', $userId)
                ->whereIn($attribute, Arr::flatten((array)$value))
                ->exists();

            return !$result;
        });

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->loadViewsFrom(__DIR__ . '/Stubs', 'ronasit');
    }

    public function register()
    {
        app(ExcelServiceProvider::class, ['app' => app()])->register();
    }
}
