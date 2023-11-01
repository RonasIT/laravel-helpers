<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Arr;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Traits\FixturesTrait;
use RonasIT\Support\Traits\AuthTestTrait;

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

        $loginSession = array_filter($session->all(), function ($key) {
            return strpos($key, 'login_session_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->assertNotEmpty($loginSession);
        $this->assertEquals('laravel_session', $session->getName());
        $this->assertTrue(array_values($loginSession)[0] === $userId);
    }

    public function testActingWithEmptySession()
    {
        $session = Arr::get($this->app['session']->getDrivers(), 'array', collect());

        $loginSession = array_filter($session->all(), function ($key) {
            return strpos($key, 'login_session_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->assertEmpty($loginSession);
    }
}
