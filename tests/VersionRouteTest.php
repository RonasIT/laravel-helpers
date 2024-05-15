<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Routing\RouteFacade;

class VersionRouteTest extends HelpersTestCase
{
    protected const ROUTE_FACADE = '/test-facade';
    protected const ROUTE_OBJECT = '/test-object';

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(VersionEnumContract::class, VersionEnum::class);

        switch ($this->getProvidedData()['route']) {
            case static::ROUTE_FACADE:
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
                break;
            case static::ROUTE_OBJECT:
                $versionFrom = $this->createMock(VersionEnumContract::class);
                $versionFrom->value = VersionEnum::v1;
                $versionTo = $this->createMock(VersionEnumContract::class);
                $versionTo->value = VersionEnum::v2;
                Route::group(['prefix' => 'v{version}'], function () use ($versionTo, $versionFrom) {
                    Route::get(static::ROUTE_OBJECT, function () {
                        return 'Route';
                    })->versionRange($versionFrom, $versionTo);
                });
                break;
        }
    }

    public function getTestVersionRangeData(): array
    {
        return [
            [
                'version' => '1',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '1.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '0.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '4',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT,
            ],

            [
                'version' => '11',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '11.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '10.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '14',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '12',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE,
            ],
            [
                'version' => '12.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE,
            ],
        ];
    }

    /**
     * @dataProvider getTestVersionRangeData
     */
    public function testVersionRange(string $version, bool $isCorrectVersion, string $route): void
    {
        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }
}