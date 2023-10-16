<?php

namespace RonasIT\Support\Tests;

use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Support\Facades\Config;
use Mockery;
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

    public function testCacheJsonFields()
    {
        Config::set('database.default', 'mysql');

        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock->shouldReceive('getColumnListing')
            ->andReturn(['json_column']);
        $mock->shouldReceive('getColumnType')
            ->andReturn('json');

        $this->cacheJsonFields('users');
        $this->assertNotEmpty(self::$jsonFields);
        $this->assertEquals(['json_column'], self::$jsonFields['users']);

        self::$jsonFields = [];
    }

    public function testWithoutCacheJsonFields()
    {
        Config::set('database.default', 'mysql');

        $mock = Mockery::mock('overload:' . MysqlBuilder::class);
        $mock->shouldReceive('getColumnListing')
            ->andReturn(['id', 'name']);
        $mock->shouldReceive('getColumnType')
            ->andReturn('string');

        $this->cacheJsonFields('users');
        $this->assertEmpty(self::$jsonFields['users']);
    }
}
