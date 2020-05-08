<?php

namespace RonasIT\Support\Tests;

use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RonasIT\Support\Traits\FixturesTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTest;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;


abstract class TestCase extends BaseTest
{
    use FixturesTrait;

    protected $jwt;
    protected $auth;
    protected $testNow = '2018-11-11 11:11:11';

    protected static $startedTestSuite;
    protected static $isWrappedIntoTransaction = true;

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

        $this->auth = app(JWTAuth::class);

        Carbon::setTestNow(Carbon::parse($this->testNow));

        Mail::fake();

        $this->beginDatabaseTransaction();
    }

    public function actingAs(Authenticatable $user, $driver = null)
    {
        $this->jwt = $this->auth->fromUser($user);

        return $this;
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (!empty($this->jwt)) {
            $server['HTTP_AUTHORIZATION'] = "Bearer {$this->jwt}";
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    public function tearDown(): void
    {
        $this->beforeApplicationDestroyed(function () {
            DB::disconnect();
        });

        parent::tearDown();
    }

    /**
     * Data should looks like following construction:
     * [
     *   [
     *      'emails' => string|array ,
     *      'fixture' => 'expected_rendered_fixture.html',
     *      'subject' => string|null
     *   ]
     * ]
     *
     * @param string $mailableClass
     * @param array $data
     */
    protected function assertMailEquals($mailableClass, $data)
    {
        $index = 0;

        Mail::assertSent($mailableClass, function ($mail) use ($data, &$index) {
            if (!Arr::has($data, 'emails') || !Arr::has($data, 'fixture')) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Data case must have required parameters: emails, fixture. Case index: ' . $index);
            }

            $sentEmails = Arr::pluck($mail->to, 'address');
            $currentMail = Arr::get($data, $index);
            $emails = Arr::wrap($currentMail['emails']);

            if (Arr::has($currentMail, 'subject')) {
                $this->assertEquals(Arr::get($currentMail, 'subject'), $mail->subject);
            }

            $this->assertEquals(count($emails), count($mail->to));

            $emailList = implode(',', $sentEmails);

            foreach ($emails as $email) {
                $this->assertContains($email, $sentEmails, "Block \"To\" on {$index} step don't contains {$email}. Contains only {$emailList}.");
            }

            $this->assertEquals(
                $this->getFixture($currentMail['fixture']),
                view($mail->view, $mail->getData())->render(),
                "Fixture {$currentMail['fixture']} does not equals rendered mail."
            );

            $index++;

            return true;
        });

        $this->assertEquals(count($data), $index, 'You have a message that was not sent. Case index: ' . $index);
    }

    protected function dontWrapIntoTransaction()
    {
        $this->rollbackTransaction();

        self::$isWrappedIntoTransaction = false;
    }

    protected function beginDatabaseTransaction()
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

    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact') ? $this->connectionsToTransact : [null];
    }

    protected function rollbackTransaction()
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
