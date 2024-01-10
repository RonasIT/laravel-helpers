<?php

namespace RonasIT\Support\Tests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTest;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RonasIT\Support\Traits\AssertTrait;
use RonasIT\Support\Traits\FixturesTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTest
{
    use AssertTrait;

    protected $auth;

    protected $testNow = '2018-11-11 11:11:11';

    protected static $startedTestSuite;
    protected static $isWrappedIntoTransaction = true;

    private $requiredExpectationParameters = [
        'emails',
        'fixture'
    ];

    protected $globalExportMode = false;

    protected function setGlobalExportMode()
    {
        $this->globalExportMode = true;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('cache:clear');

        if ((static::$startedTestSuite !== static::class) || !self::$isWrappedIntoTransaction) {
            $this->artisan('migrate');

            $this->loadTestDump();

            static::$startedTestSuite = static::class;
        }

        if (config('database.default') === 'pgsql') {
            $this->prepareSequences($this->getTables());
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
}
