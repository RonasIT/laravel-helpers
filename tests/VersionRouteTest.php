<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Event\Code\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;
use RonasIT\Support\Tests\Support\Mock\TestCaseMock;
use RonasIT\Support\Testing\TestCase as PackageTestCase;

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
        $mock = $this
            ->getMockBuilder(PackageTestCase::class)
            ->onlyMethods(['call'])
            ->setConstructorArgs(['name'])
            ->getMock()
            ->withoutAPIVersion();

        Route::get('/test', function () {
            return 'test';
        });

        $mock->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('get'),
                $this->callback(function ($uri) {
                    $this->assertEquals('/test', $uri);
                    return true;
                }),
            )
            ->willReturn(TestResponse::fromBaseResponse(response('test', 200)));

        $response = $mock->json('get', '/test');

        $response->assertOk();
    }

    public function testRouteWithSetApiVersion(): void
    {
        $mock = $this
            ->getMockBuilder(PackageTestCase::class)
            ->onlyMethods(['call'])
            ->setConstructorArgs(['name'])
            ->getMock()
            ->setAPIVersion(VersionEnum::V1);

        Route::version(VersionEnum::V1)->group(function () {
            Route::get('/test', function () {
                return 'test';
            });
        });

        $mock->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('get'),
                $this->callback(function ($uri) {
                    $this->assertEquals('/v1/test/', $uri);
                    return true;
                }),
            )
            ->willReturn(TestResponse::fromBaseResponse(response('test', 200)));

        $response = $mock->json('get', '/test/');

        $response->assertOk();
    }

    public function testRouteWithIncorrectVersion(): void
    {
        $mock = $this
            ->getMockBuilder(PackageTestCase::class)
            ->onlyMethods(['call'])
            ->setConstructorArgs(['name'])
            ->getMock()
            ->withoutAPIVersion();

        Route::version(VersionEnum::V1)->group(function () {
            Route::get('/test', function () {
                return 'test';
            });
        });

        $mock->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('get'),
                $this->callback(function ($uri) {
                    $this->assertNotEquals('/v1/test/', $uri);
                    return true;
                }),
            )
            ->willReturn(TestResponse::fromBaseResponse(response(['message' => 'The route test could not be found.'], 404)));

        $response = $mock->json('get', '/test/');

        $response->assertNotFound();

        $response->assertJson(['message' => 'The route test could not be found.']);
    }
}
