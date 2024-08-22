<?php

namespace RonasIT\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use RonasIT\Support\Contracts\VersionEnumContract as Version;
use RonasIT\Support\Middleware\SecurityMiddleware;
use Illuminate\Routing\Router;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        $this->extendValidator();

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->extendRouter();
    }

    public function register(): void
    {
        app(ExcelServiceProvider::class, ['app' => app()])->register();
    }

    protected function extendValidator(): void
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

    protected function extendRouter(): void
    {
        /**
         * Specify that the route version must be in the range of given values inclusive.
         *
         * @param Version|null $start
         * @param Version|null $end
         * @param string|null $param (default is 'version')
         * @param Route|null $instance
         * @return Router|Route
         */
        $versionRange = function (
            ?Version $start,
            ?Version $end,
            ?string $param,
            Route $instance = null
        ) {
            if (!$param) {
                $param = 'version';
            }

            $versionEnum = app(Version::class);
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

        Route::macro(
            'versionRange',
            fn (Version $from, Version $to, $param = null) => $versionRange($from, $to, $param, $this)
        );
        Route::macro('versionFrom', fn (Version $from, $param = null) => $versionRange($from, null, $param, $this));
        Route::macro('versionTo', fn (Version $to, $param = null) => $versionRange(null, $to, $param, $this));

        RouteFacade::macro(
            'versionRange',
            fn (Version $from, Version $to, string $param = null) => $versionRange($from, $to, $param)
        );
        RouteFacade::macro('versionFrom', fn (Version $from, $param = null) => $versionRange($from, null, $param));
        RouteFacade::macro('versionTo', fn (Version $to, $param = null) => $versionRange(null, $to, $param));
        RouteFacade::macro('version', fn (Version $version) => RouteFacade::prefix('v' . $version->value));
    }
}
