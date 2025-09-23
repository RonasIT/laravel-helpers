<?php

namespace RonasIT\Support\Testing;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Testing\TestResponse;
use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Traits\MailsMockTrait;

abstract class TestCase extends BaseTest
{
    use MailsMockTrait;

    protected const int REDIS_COUNT_DATABASES = 16;
    protected $auth;

    protected string $testNow = '2018-11-11 11:11:11';

    protected static string $startedTestSuite = '';
    protected static bool $isWrappedIntoTransaction = true;

    protected ?VersionEnumContract $apiVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('cache:clear');

        if ((static::$startedTestSuite !== static::class) || !self::$isWrappedIntoTransaction) {
            $this->artisan('migrate');

            $this->loadTestDump();

            static::$startedTestSuite = static::class;
        }

        $this->configureRedis();

        if (config('database.default') === 'pgsql') {
            $this->prepareSequences();
        }

        Carbon::setTestNow(Carbon::parse($this->testNow));

        Mail::fake();

        $this->beginDatabaseTransaction();
    }

    public function tearDown(): void
    {
        $this->beforeApplicationDestroyed(function () {
            DB::disconnect();
        });

        parent::tearDown();
    }

    public function callRawRequest(string $method, string $uri, $content, array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call($method, $uri, [], [], [], $server, $content);
    }

    protected function dontWrapIntoTransaction(): void
    {
        $this->rollbackTransaction();

        self::$isWrappedIntoTransaction = false;
    }

    protected function beginDatabaseTransaction(): void
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () {
            $this->rollbackTransaction();
        });
    }

    protected function connectionsToTransact(): array
    {
        return property_exists($this, 'connectionsToTransact') ? $this->connectionsToTransact : [null];
    }

    protected function rollbackTransaction(): void
    {
        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->rollback();
            $connection->setEventDispatcher($dispatcher);
            $connection->disconnect();
        }
    }

    protected function prepareModelTestState(string $modelClassName): ModelTestState
    {
        return (new ModelTestState($modelClassName))->setGlobalExportMode($this->globalExportMode);
    }

    protected function prepareTableTestState(string $tableName, array $jsonFields = [], ?string $connectionName = null): TableTestState
    {
        return (new TableTestState($tableName, $jsonFields, $connectionName))->setGlobalExportMode($this->globalExportMode);
    }

    public function withoutAPIVersion(): TestCase
    {
        return $this->setAPIVersion(null);
    }

    public function setAPIVersion(?VersionEnumContract $apiVersion): TestCase
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    public function json($method, $uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $version = (empty($this->apiVersion)) ? '' : "/v{$this->apiVersion->value}";

        return parent::json($method, "{$version}{$uri}", $data, $headers);
    }

    public function actingAs(Authenticatable $user, $guard = null): self
    {
        return parent::actingAs(clone $user, $guard);
    }

    protected function configureRedis(): void
    {
        $token = ParallelTesting::token();

        if ($token) {
            config(['database.redis.default.database' => intval($token) % self::REDIS_COUNT_DATABASES]);
        }
    }
}
