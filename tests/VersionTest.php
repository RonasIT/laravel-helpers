<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Route;
use RonasIT\Support\Support\Version;
use RonasIT\Support\Contracts\VersionEnumContract;
use Illuminate\Http\Request;

class VersionTest extends HelpersTestCase
{
    protected Request $request;

    public function setUp(): void
    {
        parent::setUp();

        Route::group(['prefix' => 'v{version}'], function () {
            Route::get('/test', function () {
                return 'Hello';
            });
        });

        $this->request = Request::create('/v1/test');

        $this->app->instance('request', $this->request);
    }

    public function getTestCurrentData(): array
    {
        return [
            [
                'version' => '1',
                'assert' => true,
            ],
            [
                'version' => '1.0',
                'assert' => false,
            ],
            [
                'version' => '0.99',
                'assert' => false,
            ],
            [
                'version' => '1.01',
                'assert' => false,
            ],
            [
                'version' => '10',
                'assert' => false,
            ],
        ];
    }

    /**
     * @dataProvider getTestCurrentData
     */
    public function testCurrent(string $version, bool $assert)
    {
        switch ($assert) {
            case true:
                $this->assertEquals($version, Version::current());
                break;
            case false:
                $this->assertNotEquals($version, Version::current());
                break;
        }
    }

    public function getTestIsData(): array
    {
        return [
            [
                'version' => '1',
                'assert' => true,
            ],
            [
                'version' => '10',
                'assert' => false,
            ],
        ];
    }

    /**
     * @dataProvider getTestIsData
     */
    public function testIs(string $version, bool $assert)
    {
        $checkedVersion = $this->createMock(VersionEnumContract::class);
        $checkedVersion->value = $version;

        $result = Version::is($checkedVersion);

        switch ($assert) {
            case true:
                $this->assertTrue($result);
                break;
            case false:
                $this->assertFalse($result);
                break;
        }
    }

    public function getTestBetweenData(): array
    {
        return [
            [
                'from' => '0.5',
                'to' => '1.5',
                'assert' => true,
            ],
            [
                'from' => '0.5',
                'to' => '0.9',
                'assert' => false,
            ],
            [
                'from' => '1.1',
                'to' => '1.8',
                'assert' => false,
            ],
            [
                'from' => '0.8555555555',
                'to' => '1.0',
                'assert' => true,
            ],
            [
                'from' => '1',
                'to' => '1.2',
                'assert' => true,
            ],
            [
                'from' => '1.0',
                'to' => '2',
                'assert' => false,
            ],
        ];
    }

    /**
     * @dataProvider getTestBetweenData
     */
    public function testBetween(string $from, string $to, bool $assert)
    {
        $checkedVersionFrom = $this->createMock(VersionEnumContract::class);
        $checkedVersionFrom->value = $from;

        $checkedVersionTo = $this->createMock(VersionEnumContract::class);
        $checkedVersionTo->value = $to;

        $result = Version::between($checkedVersionFrom, $checkedVersionTo);

        switch ($assert) {
            case true:
                $this->assertTrue($result);
                break;
            case false:
                $this->assertFalse($result);
                break;
        }
    }

    public function getTestGteData(): array
    {
        return [
            [
                'version' => '0.5',
                'assert' => true,
            ],
            [
                'version' => '1',
                'assert' => true,
            ],
            [
                'version' => '1.0',
                'assert' => false,
            ],
            [
                'version' => '2.0',
                'assert' => false,
            ],
        ];
    }

    /**
     * @dataProvider getTestGteData
     */
    public function testGte(string $version, bool $assert)
    {
        $checkedVersion = $this->createMock(VersionEnumContract::class);
        $checkedVersion->value = $version;

        $result = Version::gte($checkedVersion);

        switch ($assert) {
            case true:
                $this->assertTrue($result);
                break;
            case false:
                $this->assertFalse($result);
                break;
        }
    }

    public function getTestLteData(): array
    {
        return [
            [
                'version' => '1',
                'assert' => true,
            ],
            [
                'version' => '1.0',
                'assert' => true,
            ],
            [
                'version' => '2',
                'assert' => true,
            ],
            [
                'version' => '0.5',
                'assert' => false,
            ],
        ];
    }

    /**
     * @dataProvider getTestLteData
     */
    public function testLte(string $version, bool $assert)
    {
        $checkedVersion = $this->createMock(VersionEnumContract::class);
        $checkedVersion->value = $version;

        $result = Version::lte($checkedVersion);

        switch ($assert) {
            case true:
                $this->assertTrue($result);
                break;
            case false:
                $this->assertFalse($result);
                break;
        }
    }
}
