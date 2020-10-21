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

    private $assertMailRequiredParameters = [
        'emails',
        'fixture'
    ];

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
    * Email Chain should looks like following construction:
    *   [
    *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
    *      'fixture' => 'expected_rendered_fixture.html', fixture name  to which send email sexpected to be equal on the step 1
    *      'subject' => string|null, expected email subject from the step 1
    *   ]
    *
    * or be array, if sent more than 1 email:
    *
    * [
    *   [
    *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
    *      'fixture' => 'expected_rendered_fixture.html', fixture name  to which send email sexpected to be equal on the step 1
    *      'subject' => string|null, expected email subject from the step 1
    *   ]
    * ],
    * ...
    * [
    *   [
    *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step N
    *      'fixture' => 'expected_rendered_fixture.html', fixture name  to which send email sexpected to be equal on the step N
    *      'subject' => string|null, expected email subject from the step N
    *   ]
    * ]
    *
    * or json fixture filename with data in the formats indicated above:
    *
    * fuxture_file_name.json
    *
    * @param string $mailableClass
    * @param mixed $emailChain
    */

    protected function assertMailsEquals($mailableClass, $emailChain)
    {
        if (is_string($emailChain)) {
            $emailChain = $this->getJsonFixture($emailChain);
        }

        $isMultiAssert = is_array(Arr::first($emailChain));
        $index = 0;

        Mail::assertSent($mailableClass, function ($mail) use ($emailChain, $isMultiAssert, &$index) {
            $sentEmails = Arr::pluck($mail->to, 'address');
            $currentMail = ($isMultiAssert) ? Arr::get($emailChain, $index) : $emailChain;

            $this->validateMailParameters($currentMail, $index);

            $emails = Arr::wrap($currentMail['emails']);

            $this->assertSubject($currentMail, $mail);
            $this->assertAddressesCount($emails, $mail, $index);
            $this->assertSentEmailList($sentEmails, $emails, $index);

            $this->assertEquals(
                $this->getFixture($currentMail['fixture']),
                view($mail->view, $mail->getData())->render(),
                "Fixture {$currentMail['fixture']} does not equals rendered mail."
            );

            $index++;

            return true;
        });

        $countData = count($emailChain);

        $this->assertCountMails($isMultiAssert, $countData, $index);
    }

    protected function assertMailEquals($mailableClass, $email, $fixture, $subject = null)
    {
        $emailChain = [
            'emails' => $email,
            'fixture' => $fixture,
        ];

        if (!empty($subject)) {
            $emailChain['subject'] = $subject;
        }

        $this->assertMailsEquals($mailableClass, [$emailChain]);
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

    private function validateMailParameters($currentMail, $index)
    {
        foreach ($this->assertMailRequiredParameters as $parameter) {
            if (!Arr::has($currentMail, $parameter)) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, "Missing required key '{$parameter}' in the input data set on the step: {$index}");
            }
        }
    }

    private function assertSubject($currentMail, $mail): void
    {
        if (!empty(Arr::get($currentMail, 'subject'))) {
            $expectedSubject = Arr::get($currentMail, 'subject');
            $this->assertEquals($expectedSubject, $mail->subject, "Failed assert that the expected subject '{$expectedSubject}' equals to the actual '{$mail->subject}'");
        }
    }

    private function assertAddressesCount(array $emails, $mail, int $index): void
    {
        $expectedAddressesCount = count($emails);
        $addressesCount = count($mail->to);

        $this->assertEquals($expectedAddressesCount, $addressesCount, "Failed assert that email on the step {$index}, was sent to {$expectedAddressesCount} addresses, actually email had sent to the {$addressesCount} addresses");
    }

    private function assertSentEmailList(array $sentEmails, array $emails, int $index): void
    {
        $emailList = implode(',', $sentEmails);

        foreach ($emails as $email) {
            $this->assertContains($email, $sentEmails, "Block \"To\" on {$index} step don't contains {$email}. Contains only {$emailList}.");
        }
    }

    private function assertCountMails(bool $isMultiAssert, int $countData, int $index): void
    {
        if ($isMultiAssert) {
            $this->assertEquals($countData, $index, "Failed assert that send emails count are equals, expected send email count: {$countData}, actual {$index}");
        }
    }
}
