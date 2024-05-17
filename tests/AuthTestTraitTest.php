<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use RonasIT\Support\Tests\Support\Mock\MockAuthUser;
use RonasIT\Support\Traits\AuthTestTrait;
use RonasIT\Support\Traits\FixturesTrait;

class AuthTestTraitTest extends HelpersTestCase
{
    use AuthTestTrait;
    use FixturesTrait;

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

    public function testActingViaSessionDifferentGuard()
    {
        $userId = 1;

        $this->actingViaSession($userId, 'some_guard');

        $session = $this->app['session']->getDrivers()['array'];
        $loginSession = $this->getLoginSession($session, 'some_guard');

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

    public function testActingAs()
    {
        $mockedUser = new MockAuthUser();
        $mockedUser->someIntProperty = 0;

        $this->actingAs($mockedUser);

        $user = Auth::user();
        $user->someIntProperty = 1;

        $this->actingAs($mockedUser);

        $this->assertEquals(0, Auth::user()->someIntProperty);
    }
}
