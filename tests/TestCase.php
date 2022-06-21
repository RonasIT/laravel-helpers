<?php

namespace RonasIT\Support\Tests;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RonasIT\Support\Traits\FixturesTrait;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Testing\TestCase as BaseTest;

abstract class TestCase extends BaseTest
{
    use FixturesTrait;

    protected $auth;

    protected $testNow = '2018-11-11 11:11:11';

    protected static $startedTestSuite;
    protected static $isWrappedIntoTransaction = true;

    private $requiredExpectationParameters = [
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

    /**
     * Email Chain should look like following construction:
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *   ]
     *
     * or be an array, if sent more than 1 email:
     *
     * [
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *   ]
     * ],
     * ...
     * [
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step N
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step N
     *      'subject' => string|null, expected email subject from the step N
     *   ]
     * ]
     *
     * or json fixture filename with data in the formats indicated above:
     *
     * fixture_file_name.json
     *
     * Export mode will export html to fixture before assert
     *
     * @param string $mailableClass
     * @param mixed $emailChain
     * @param mixed $exportMode
     */
    protected function assertMailEquals(string $mailableClass, array $emailChain, bool $exportMode = false): void
    {
        $emailChain = $this->prepareEmailChain($emailChain);
        $index = 0;

        Mail::assertSent($mailableClass, $this->assertSentCallback($emailChain, $index, $exportMode));

        $this->assertCountMails($emailChain, $index);
    }

    protected function assertSentCallback(array $emailChain, int &$index, bool $exportMode = false): Closure
    {
        return function ($mail) use ($emailChain, &$index, $exportMode) {
            $expectedMailData = Arr::get($emailChain, $index);
            $this->validateExpectationParameters($expectedMailData, $index);

            $this->assertSubject($expectedMailData, $mail);
            $this->assertEmailsList($expectedMailData, $mail, $index);
            $this->assertFixture($expectedMailData, $mail, $exportMode);

            $index++;

            return true;
        };
    }

    protected function validateExpectationParameters(array $currentMail, int $index): void
    {
        foreach ($this->requiredExpectationParameters as $parameter) {
            if (!Arr::has($currentMail, $parameter)) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, "Missing required key \"{$parameter}\" in the input data set on the step: {$index}.");
            }
        }
    }

    protected function assertSubject(array $currentMail, Mailable $mail): void
    {
        $expectedSubject = Arr::get($currentMail, 'subject');

        if (!empty($expectedSubject)) {
            $this->assertEquals($expectedSubject, $mail->subject, "Failed assert that the expected subject \"{$expectedSubject}\" equals to the actual \"{$mail->subject}\".");
        }
    }

    protected function assertAddressesCount(array $emails, Mailable $mail, int $index): void
    {
        $expectedAddressesCount = count($emails);
        $addressesCount = count($mail->to);

        $this->assertEquals($expectedAddressesCount, $addressesCount, "Failed assert that email on the step {$index}, was sent to {$expectedAddressesCount} addresses, actually email had sent to the {$addressesCount} addresses.");
    }

    protected function assertSentToEmailsList(array $sentEmails, array $emails, int $index): void
    {
        $emailList = implode(',', $sentEmails);

        foreach ($emails as $email) {
            $this->assertContains($email, $sentEmails, "Block \"To\" on {$index} step don't contains {$email}. Contains only {$emailList}.");
        }
    }

    protected function assertCountMails(array $emailChain, int $index): void
    {
        $countData = count($emailChain);

        $this->assertEquals($countData, $index, "Failed assert that send emails count are equals, expected send email count: {$countData}, actual {$index}.");
    }

    protected function assertEmailsList(array $expectedMailData, Mailable $mail, int $index): void
    {
        $sentEmails = Arr::pluck($mail->to, 'address');
        $emails = Arr::wrap($expectedMailData['emails']);
        $this->assertAddressesCount($emails, $mail, $index);
        $this->assertSentToEmailsList($sentEmails, $emails, $index);
    }

    protected function assertFixture(array $expectedMailData, Mailable $mail, bool $exportMode = false): void
    {
        $mailContent = view($mail->view, $mail->getData())->render();

        if ($exportMode) {
            $this->exportContent($mailContent, $expectedMailData['fixture']);
        }

        $fixture = $this->getFixture($expectedMailData['fixture']);

        $this->assertEquals($fixture, $mailContent, "Fixture {$expectedMailData['fixture']} does not equals rendered mail.");
    }

    protected function prepareEmailChain($emailChain): array
    {
        if (is_string($emailChain)) {
            $emailChain = $this->getJsonFixture($emailChain);
        }

        return (is_multidimensional($emailChain)) ? $emailChain : [$emailChain];
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
