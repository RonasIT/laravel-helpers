<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;
use RonasIT\Support\Tests\Support\Mock\TestCaseMock;

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

        $this->app->bind(VersionEnumContract::class, VersionEnum::class);
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
        $testCaseMock = (new TestCaseMock('name'))
            ->setUpMock($this->app)
            ->withoutAPIVersion();

        Route::get('/test', function () {
            return 'test';
        });

        $response = $testCaseMock->json('get', '/test');

        $response->assertOk();
    }

    public function testRouteWithSetApiVersion(): void
    {
        $version = $this->createMock(VersionEnumContract::class);
        $version->value = VersionEnum::v1;

        $testCaseMock = (new TestCaseMock('name'))
            ->setUpMock($this->app)
            ->setAPIVersion($version);

        Route::version($version)->group(function () {
            Route::get('/test', function () {
                return 'test';
            });
        });

        $response = $testCaseMock->json('get', '/test/');

        $response->assertOk();
    }

    public function testRouteWithLatestVersion(): void
    {
        $testCaseMock = (new TestCaseMock('name'))
            ->setUpMock($this->app);

        Route::version(VersionEnum::getLatest())->group(function () {
            Route::get('/test', function () {
                return 'test';
            });
        });

        $response = $testCaseMock->json('get', '/test/');

        $response->assertOk();
    }

    public function testRouteWithIncorrectVersion(): void
    {
        $version = $this->createMock(VersionEnumContract::class);
        $version->value = VersionEnum::v1;

        $testCaseMock = (new TestCaseMock('name'))
            ->setUpMock($this->app)
            ->setAPIVersion($version);

        Route::version(VersionEnum::getLatest())->group(function () {
            Route::get('/test', function () {
                return 'test';
            });
        });

        $response = $testCaseMock->json('get', '/test/');

        $response->assertNotFound();

        $response->assertJson(['message' => 'The route v1/test could not be found.']);
    }
}
