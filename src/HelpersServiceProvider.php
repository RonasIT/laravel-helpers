<?php

namespace RonasIT\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\ExcelServiceProvider;
use RonasIT\Support\Middleware\SecurityMiddleware;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        Validator::extend('unique_except_of_authorized_user', function ($attribute, $value) {
            if (gettype($value) !== 'string') {
                throw new UnprocessableEntityHttpException("The {$attribute} must be a string.");
            }

            $userId = Auth::id();
            $result = DB::select("select count(*) as entities_count from users where id <> {$userId} and {$attribute} = '{$value}';");

            return $result[0]['entities_count'] == 0;
        });

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->loadViewsFrom(__DIR__ . '/Stubs', 'ronasit');
    }

    public function register()
    {
        app(ExcelServiceProvider::class, ['app' => app()])->register();
    }
}
