<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use RonasIT\Support\Tests\Support\Mock\Models\MockAuthUser;
use RonasIT\Support\Traits\FixturesTrait;
use RonasIT\Support\Tests\Support\Mock\TestCaseMock;

class AuthTest extends TestCase
{
    use FixturesTrait;

    protected TestCaseMock $testCaseMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->testCaseMock = (new TestCaseMock('name'))
            ->setUpMock($this->app);
    }

    public function testActingViaSession()
    {
        $userId = 1;

        $this->testCaseMock->actingViaSession($userId);

        $session = $this->app['session']->getDrivers()['array'];
        $loginSession = $this->getLoginSession($session);

        $this->assertNotEmpty($loginSession);
        $this->assertEquals('laravel_session', $session->getName());
        $this->assertEquals(array_values($loginSession), [$userId]);
    }

    public function testActingViaSessionDifferentGuard()
    {
        $userId = 1;

        $this->testCaseMock->actingViaSession($userId, 'some_guard');

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

        $this->testCaseMock->actingAs($mockedUser);

        $this->assertNotSame($mockedUser, Auth::user());
    }
}