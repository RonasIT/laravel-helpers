<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Exceptions\BindingVersionEnumException;
use RonasIT\Support\Http\Middleware\CheckVersionMiddleware;
use RonasIT\Support\Http\Middleware\ClearVersion;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;
use Symfony\Component\HttpFoundation\Response;

class VersionRouteTest extends TestCase
{
    use RouteMockTrait;

    protected const ROUTE_FACADE_RANGE = '/test-facade-range';
    protected const ROUTE_FACADE_FROM = '/test-facade-from';
    protected const ROUTE_FACADE_TO = '/test-facade-to';
    protected const ROUTE_OBJECT_RANGE = '/test-object-range';
    protected const ROUTE_OBJECT_FROM = '/test-object-from';
    protected const ROUTE_OBJECT_TO = '/test-object-to';

    protected const ROUTE_FACADE_VERSION = '/test-facade-version';

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);
    }

    public static function getTestVersionRangeData(): array
    {
        return [
            [
                'version' => '1',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '1.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '0.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '4',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '1.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '1',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '1.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '0.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '4',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '1.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
        ];
    }

    #[DataProvider('getTestVersionRangeData')]
    public function testVersionRange(string $version, bool $isCorrectVersion, string $route): void
    {
        $this->mockRoutes();

        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }

    public static function getTestVersionFromData(): array
    {
        return [
            [
                'version' => '1',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '3',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '3.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '1',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
        ];
    }

    #[DataProvider('getTestVersionFromData')]
    public function testVersionFrom(string $version, bool $isCorrectVersion, string $route): void
    {
        $this->mockRoutes();

        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }

    public static function getTestVersionToData(): array
    {
        return [
            [
                'version' => '1',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '3',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '1.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],

            [
                'version' => '1',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_TO,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_TO,
            ],
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_TO,
            ],
            [
                'version' => '3',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_TO,
            ],
            [
                'version' => '1.5',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_TO,
            ],
        ];
    }

    #[DataProvider('getTestVersionToData')]
    public function testVersionTo(string $version, bool $isCorrectVersion, string $route): void
    {
        $this->mockRoutes();

        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }

    public static function getTestVersionData(): array
    {
        return [
            [
                'version' => '2',
                'isCorrectVersion' => true,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '2.0',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '1',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '3',
                'isCorrectVersion' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
        ];
    }

    #[DataProvider('getTestVersionData')]
    public function testVersion(string $version, bool $isCorrectVersion, string $route): void
    {
        $this->mockRoutes();

        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }

    public function testWithoutApiVersion(): void
    {
        $mock = $this->mockTestCaseExpectCallMethod('/test');

        $mock
            ->withoutAPIVersion()
            ->json('get', '/test');
    }

    public function testRouteWithSetApiVersion(): void
    {
        $mock = $this->mockTestCaseExpectCallMethod('/v1/test/');

        $mock
            ->setApiVersion(VersionEnum::V1)
            ->json('get', '/test/');
    }

    public function testVersionEnumContractIsNotBound()
    {
        $this->app->offsetUnset(VersionEnumContract::class);

        $this->assertExceptionThrew(
            expectedClassName: BindingVersionEnumException::class,
            expectedMessage: 'The VersionEnumContract is not bound in the container.'
                . ' Please ensure it is registered using'
                . ' $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);',
        );

        Route::versionRange(VersionEnum::V1, VersionEnum::V2)->group(function () {
            Route::get('test', fn () => 'result');
        });
    }

    public function testCheckVersionMiddlewareDisabled()
    {
        Config::set('app.disabled_api_versions', [VersionEnum::V1->value]);

        Route::version(VersionEnum::V1)
            ->middleware(CheckVersionMiddleware::class)
            ->group(function () {
                Route::get('/test-middleware', fn () => response()->noContent());
            });

        $this
            ->getJson('/v1/test-middleware')
            ->assertStatus(Response::HTTP_UPGRADE_REQUIRED);
    }

    public function testCheckVersionMiddlewareEnabled()
    {
        Config::set('app.disabled_api_versions', [VersionEnum::V1->value]);

        Route::prefix('v{version}')
            ->middleware([
                CheckVersionMiddleware::class,
                ClearVersion::class,
            ])
            ->group(function () {
                Route::versionFrom(VersionEnum::V1)
                    ->group(function () {
                        Route::get('/test-middleware', fn() => response()->noContent());
                    });
            });

        $this
            ->getJson('/v2/test-middleware')
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }
}
