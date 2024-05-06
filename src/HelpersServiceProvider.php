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
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Middleware\SecurityMiddleware;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->extendRouter();

        /**
         * Specify that the route version must be in the range of given values inclusive.
         *
         * @param VersionEnumContract|null $start
         * @param VersionEnumContract|null $end
         * @param string $param (default is 'version')
         * @return Route
         */
        Route::macro('versionRange', function (?VersionEnumContract $start, ?VersionEnumContract $end, string $param = 'version') {
            $versions = array_diff(VersionEnumContract::values(), config('app.disabled_api_versions'));

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

            return $this->whereIn($param, $versions);
        });

        Route::macro('versionFrom', fn (VersionEnumContract $from) => $this::versionRange($from, null));

        Route::macro('versionTo', fn (VersionEnumContract $to) => $this::versionRange(null, $to));

        RouteFacade::macro('version', fn (VersionEnumContract $version) => RouteFacade::prefix('v' . $version->value));
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
        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        $this->extendValidator();

        app(ExcelServiceProvider::class, ['app' => app()])->boot();

        $this->loadViewsFrom(__DIR__ . '/Stubs', 'ronasit');
    }
}
