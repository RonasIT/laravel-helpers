<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;
use RonasIT\Support\Tests\Support\Traits\RouteMockTrait;

class VersionRouteTest extends HelpersTestCase
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

    public function getTestVersionRangeData(): array
    {
        return [
            // Range
            [
                'version' => '1',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '1.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '0.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '4',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],
            [
                'version' => '1.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_RANGE,
            ],

            [
                'version' => '1',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '1.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '0.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '4',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],
            [
                'version' => '1.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_RANGE,
            ],

            // From
            [
                'version' => '1',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '3',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_FROM,
            ],
            [
                'version' => '3.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_FROM,
            ],

            [
                'version' => '1',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],

            // To
            [
                'version' => '1',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '3',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],
            [
                'version' => '1.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_OBJECT_TO,
            ],

            [
                'version' => '1',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_FROM,
            ],
            [
                'version' => '3.5',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_FROM,
            ],

            // Version
            [
                'version' => '2',
                'is_correct_version' => true,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '2.0',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '1',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
            [
                'version' => '3',
                'is_correct_version' => false,
                'route' => static::ROUTE_FACADE_VERSION,
            ],
        ];
    }

    /**
     * @dataProvider getTestVersionRangeData
     */
    public function testVersionRange(string $version, bool $isCorrectVersion, string $route): void
    {
        $this->mockRoutes();

        $response = $this->get("/v{$version}{$route}");

        $status = ($isCorrectVersion) ? 200 : 404;

        $response->assertStatus($status);
    }
}