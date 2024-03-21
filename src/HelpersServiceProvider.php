<?php

namespace RonasIT\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
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
            $table = Arr::get($parameters, 0, 'users');
            $keyField = Arr::get($parameters, 1, 'id');
            $userId = Auth::id();

            $result = DB::table($table)
                ->where($keyField, '<>', $userId)
                ->whereIn($attribute, Arr::flatten((array) $value))
                ->exists();

            return !$result;
        });

        Validator::extend('list_exists', function ($attribute, $value, $parameters) {

            if (count($parameters) < 1) {
                throw new InvalidArgumentException("Validation rule `list_exists` requires at least 1 parameters.");
            }

            $table = Arr::get($parameters, 0);
            $keyField = Arr::get($parameters, 1, 'id');

            if (!empty(Arr::get($parameters, 2))) {
                $value = collect($value)->pluck(Arr::get($parameters, 2));
            }

            return DB::table($table)
                ->whereIn($keyField, $value)
                ->exists();
        });

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->loadViewsFrom(__DIR__ . '/Stubs', 'ronasit');
    }

    public function register()
    {
        app(ExcelServiceProvider::class, ['app' => app()])->register();
    }
}
