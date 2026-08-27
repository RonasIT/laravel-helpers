<?php

namespace RonasIT\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\Concerns\TestDatabases;
use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Contracts\VersionEnumContract as Version;
use RonasIT\Support\Exceptions\BindingVersionEnumException;
use RonasIT\Support\Http\Middleware\SecurityMiddleware;
use RonasIT\Support\Support\PostgresDBTypeResolver;
use RonasIT\Support\Support\UncountableWords;

class HelpersServiceProvider extends ServiceProvider
{
    use TestDatabases;

    public function boot(): void
    {
        URL::forceHttps();

        $router = $this->app['router'];

        $router->prependMiddlewareToGroup('web', SecurityMiddleware::class);
        $router->prependMiddlewareToGroup('api', SecurityMiddleware::class);

        $this->extendRouter();

        if ($this->app->runningUnitTests()) {
            $this->whenNotUsingInMemoryDatabase(function ($database) {
                list($testDatabase, $created) = $this->ensureTestDatabaseExists($database);

                $this->switchToDatabase($testDatabase);

                if ($created) {
                    ParallelTesting::callSetUpTestDatabaseCallbacks($testDatabase);
                }
            });
        }

        Pluralizer::$uncountable = array_unique(array_merge(Pluralizer::$uncountable, UncountableWords::LIST));
    }

    public function register(): void
    {
        $this->app->bind(DBTypeResolverContract::class, PostgresDBTypeResolver::class);

        $this->app->register(ValidationServiceProvider::class);
    }

    protected function extendRouter(): void
    {
        /**
         * Specify that the route version must be in the range of given values inclusive.
         *
         * @param  Version|null  $start
         * @param  Version|null  $end
         * @param  string|null  $param  (default is 'version')
         * @param  Route|null  $instance
         *
         * @return Router|Route
         */
        $versionRange = function (
            ?Version $start,
            ?Version $end,
            ?string $param,
            ?Route $instance = null,
        ) {
            if (!$param) {
                $param = 'version';
            }

            if (!$this->app->bound(Version::class)) {
                throw new BindingVersionEnumException();
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
            name: 'versionRange',
            macro: fn (Version $from, Version $to, $param = null) => $versionRange($from, $to, $param, $this),
        );
        Route::macro('versionFrom', fn (Version $from, $param = null) => $versionRange($from, null, $param, $this));
        Route::macro('versionTo', fn (Version $to, $param = null) => $versionRange(null, $to, $param, $this));

        RouteFacade::macro(
            name: 'versionRange',
            macro: fn (Version $from, Version $to, ?string $param = null) => $versionRange($from, $to, $param),
        );
        RouteFacade::macro('versionFrom', fn (Version $from, $param = null) => $versionRange($from, null, $param));
        RouteFacade::macro('versionTo', fn (Version $to, $param = null) => $versionRange(null, $to, $param));
        RouteFacade::macro('version', fn (Version $version) => RouteFacade::prefix('v' . $version->value));
    }
}
