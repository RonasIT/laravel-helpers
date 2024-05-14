<?php

namespace RonasIT\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RonasIT\Support\Routing\RouteFacade as RouteFacade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Middleware\SecurityMiddleware;
use Illuminate\Routing\Router;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        $this->extendValidator();

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->loadViewsFrom(__DIR__ . '/Stubs', 'ronasit');

        $this->extendRouter();
    }

    public function register()
    {
        app(ExcelServiceProvider::class, ['app' => app()])->register();
    }

    protected function extendValidator()
    {
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
                return false;
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
    }

    protected function extendRouter()
    {
        if (!method_exists(Route::class, 'whereIn')) {
            Route::macro('whereIn', function ($parameters, array $values) {
                return $this->assignExpressionToParameters($parameters, implode('|', $values));
            });

            Router::macro('assignExpressionToParameters', function($parameters, $expression) {
                return $this->where(collect(Arr::wrap($parameters))
                    ->mapWithKeys(function ($parameter) use ($expression) {
                        return [$parameter => $expression];
                    })->all());
            });

            RouteFacade::macro('whereIn', function ($parameters, array $values) {
                return static::getFacadeRoot()->assignExpressionToParameters($parameters, implode('|', $values));
            });
        }

        /**
         * Specify that the route version must be in the range of given values inclusive.
         *
         * @param VersionEnumContract|null $start
         * @param VersionEnumContract|null $end
         * @param string|null $param (default is 'version')
         * @param Route|null $instance
         * @return Router|Route
         */
        $versionRange = function (?VersionEnumContract $start, ?VersionEnumContract $end, ?string $param, Route $instance = null) {
            if (!$param) {
                $param = 'version';
            }

            $versionEnum = app(VersionEnumContract::class);
            $disabledVersions = config('app.disabled_api_versions') ?: [];

            $versions = array_diff($versionEnum::values(), $disabledVersions);

            $versions = array_filter($versions, function ($version) use ($start, $end) {
                $result = true;

                if (!empty($start)) {
                    $result &= version_compare($version, $start->value, '>=');
                }

                if (!empty($end)) {
                    $result &= version_compare($version, $end->value, '<=');
                }

                return $result;
            });

            return (!empty($instance))
                ? $instance->whereIn($param, $versions)
                : RouteFacade::whereIn($param, $versions);
        };

        Route::macro('versionRange', fn (VersionEnumContract $from, VersionEnumContract $to, $param = null) => $versionRange($from, $to, $param, $this));
        Route::macro('versionFrom', function (VersionEnumContract $from, $param = null) use ($versionRange) {
            $versionRange($from, null, $param, $this);
        });
        Route::macro('versionTo', function (VersionEnumContract $to, $param = null) use ($versionRange) {
            $versionRange(null, $to, $param, $this);
        });

        RouteFacade::macro('versionRange', function (VersionEnumContract $from, VersionEnumContract $to, string $param = null) use ($versionRange) {
            return $versionRange($from, $to, $param);
        });
        RouteFacade::macro('versionFrom', function (VersionEnumContract $from, $param = null) use ($versionRange) {
            return $versionRange($from, null, $param);
        });
        RouteFacade::macro('versionTo', function (VersionEnumContract $to, $param = null) use ($versionRange) {
            return $versionRange(null, $to, $param);
        });

        RouteFacade::macro('version', fn (VersionEnumContract $version) => RouteFacade::prefix('v' . $version->value));
    }
}
