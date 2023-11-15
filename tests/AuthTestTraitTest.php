<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Arr;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Traits\AuthTestTrait;
use RonasIT\Support\Traits\FixturesTrait;

class AuthTestTraitTest extends HelpersTestCase
{
    use FixturesTrait, MockTrait, AuthTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = [];

        putenv('FAIL_EXPORT_JSON=false');
    }

    public function testActingViaSession()
    {
        $userId = 1;

        $this->actingViaSession($userId);

        $session = $this->app['session']->getDrivers()['array'];
        $loginSession = $this->getLoginSession($session);

        $this->assertNotEmpty($loginSession);
        $this->assertEquals('laravel_session', $session->getName());
        $this->assertEquals(array_values($loginSession), [$userId]);
    }

    public function testActingWithEmptySession()
    {
        $session = Arr::get($this->app['session']->getDrivers(), 'array', collect());
        $loginSession = $this->getLoginSession($session);

        $this->assertEmpty($loginSession);
    }
}
