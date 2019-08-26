<?php

namespace App\Tests;

use Carbon\Carbon;
use App\Models\JobUser;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Console\Kernel;
use RonasIT\Support\Traits\FixturesTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use RonasIT\Support\AutoDoc\Tests\AutoDocTestCase;

abstract class TestCase extends AutoDocTestCase
{
    use FixturesTrait;

    protected $jwt;
    protected $auth;

    public function setUp()
    {
        parent::setUp();

        $this->artisan('cache:clear');
        $this->artisan('migrate');

        $this->loadTestDump(['migrations', 'password_resets', 'interview_types', 'questions', 'answers', 'character_types', 'adaptation_map'],
            ['migrations', 'password_resets', 'settings', 'questions', 'answers']);

        $this->auth = app(JWTAuth::class);

        Mail::fake();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
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

    public function tearDown()
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
            $sentEmails = Arr::pluck($mail->to, 'address');
            $currentMail = Arr::get($data, $index);
            $emails = Arr::wrap($currentMail['emails']);
            $subject = Arr::get($currentMail, 'subject');

            if (!empty($subject)) {
                $this->assertEquals($currentMail['subject'], $mail->subject);
            }

            $this->assertEquals(count($mail->to), count($emails));

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
    }
}
