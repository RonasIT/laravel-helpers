<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Enum\VersionEnum;
use RonasIT\Support\Routing\RouteFacade;

class VersionRouteTest extends HelpersTestCase
{
    protected const ROUTE_FACADE = '/test-facade';
    protected const ROUTE_OBJECT = '/test-object';

    protected bool $init = false;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(VersionEnumContract::class, VersionEnum::class);

        if (!$this->init) {
            $versionFrom = $this->createMock(VersionEnumContract::class);
            $versionFrom->value = VersionEnum::v1;
            $versionTo = $this->createMock(VersionEnumContract::class);
            $versionTo->value = VersionEnum::v2;
            Route::group(['prefix' => 'v{version}'], function () use ($versionTo, $versionFrom) {
                Route::get(static::ROUTE_OBJECT, function () {
                    return 'Route';
                })->versionRange($versionFrom, $versionTo);
            });

            $versionFrom = $this->createMock(VersionEnumContract::class);
            $versionFrom->value = VersionEnum::v11;
            $versionTo = $this->createMock(VersionEnumContract::class);
            $versionTo->value = VersionEnum::v12;
            Route::group(['prefix' => 'v{version}'], function () use ($versionTo, $versionFrom) {
                RouteFacade::versionRange($versionFrom, $versionTo)->group(function () {
                    Route::get(static::ROUTE_FACADE, function () {
                        return 'RouteFacade';
                    });
                });
            });

            $this->init = true;
        }
    }

    public function getTestVersionRangeData(): array
    {
        return [
            [
                'version' => '1',
                'assert' => true,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '1.0',
                'assert' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '0.5',
                'assert' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '4',
                'assert' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '2',
                'assert' => true,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '2.0',
                'assert' => false,
                'route' => static::ROUTE_OBJECT,
            ],

            [
                'version' => '11',
                'assert' => true,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '11.0',
                'assert' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '10.5',
                'assert' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '14',
                'assert' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '12',
                'assert' => true,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '12.0',
                'assert' => false,
                'route' => static::ROUTE_FACADE,
            ],
        ];
    }

    /**
     * @dataProvider getTestVersionRangeData
     */
    public function testVersionRange(string $version, bool $assert, string $route): void
    {
        $response = $this->get("/v{$version}{$route}");

        $status = $assert ? 200 : 404;

        $response->assertStatus($status);
    }
}