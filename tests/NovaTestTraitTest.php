<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Traits\FixturesTrait;
use RonasIT\Support\Traits\NovaTestTrait;

class NovaTestTraitTest extends HelpersTestCase
{
    use FixturesTrait, MockTrait, NovaTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        self::$tables = [];
    }

    public function testActingAsNovaUser()
    {
        $userId = 1;
        $this->novaActingAs($userId);
        $session = $this->app['session']->getDrivers()['array'];

        $loginSession = array_filter($session->all(), function ($key) {
            return str_starts_with($key, 'login_session_');
        }, ARRAY_FILTER_USE_KEY);

        $this->assertNotEmpty($loginSession);
        $this->assertEquals('laravel_session', $session->getName());
        $this->assertTrue(array_values($loginSession)[0] === $userId);
    }
}
