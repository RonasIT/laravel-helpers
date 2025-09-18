<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

trait MailsMockTrait
{
    use FixturesTrait;

    private array $requiredExpectationParameters = [
        'emails',
        'fixture',
    ];

    /**
     * Email Chain should look like following construction:
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *      'from' => string|null, expected email sender address the step 1
     *      'attachments' => array, expected attachments
     *   ]
     *
     * or be a function call:
     *
     *   $this->mockedMail($emails, $fixture, $subject, $from, $attachments),
     *
     * or be an array, if sent more than 1 email:
     *
     * [
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *      'from' => string|null, expected email sender address the step 1
     *   ],
     *   ...
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step N
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step N
     *      'subject' => string|null, expected email subject from the step N
     *      'attachments' => array, expected attachments
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
     * @param array|string $emailChain
     * @param mixed $exportMode
     */
    protected function assertMailEquals(string $mailableClass, $emailChain, bool $exportMode = false): void
    {
        $emailChain = $this->prepareEmailChain($emailChain);
        $index = 0;

        Mail::assertQueued($mailableClass, $this->assertSentCallback($emailChain, $index, $exportMode));

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
            $this->assertEmailFrom($expectedMailData, $mail);
            $this->assertAttachments($expectedMailData, $mail);

            $index++;

            return true;
        };
    }

    protected function validateExpectationParameters(array $currentMail, int $index): void
    {
        foreach ($this->requiredExpectationParameters as $parameter) {
            if (!Arr::has($currentMail, $parameter)) {
                $this->fail("Missing required key \"{$parameter}\" in the input data set on the step: {$index}.");
            }
        }
    }

    protected function assertSubject(array $currentMail, Mailable $mail): void
    {
        $expectedSubject = Arr::get($currentMail, 'subject');

        if (!empty($expectedSubject)) {
            $subject = method_exists($mail, 'envelope') ? $mail->envelope()->subject : $mail->subject;

            $this->assertTrue(
                condition: $mail->hasSubject($expectedSubject),
                message: "Failed assert that the expected subject \"{$expectedSubject}\" equals to the actual \"{$subject}\".",
            );
        }
    }

    protected function assertAddressesCount(array $emails, Mailable $mail, int $index): void
    {
        $expectedAddressesCount = count($emails);
        $addressesCount = count($mail->to);

        $this->assertEquals(
            expected: $expectedAddressesCount,
            actual: $addressesCount,
            message: "Failed assert that email on the step {$index}, was sent to {$expectedAddressesCount} addresses, actually email had sent to the {$addressesCount} addresses.",
        );
    }

    protected function assertSentToEmailsList(array $sentEmails, array $emails, int $index): void
    {
        $emailList = implode(',', $sentEmails);

        foreach ($emails as $email) {
            $this->assertContains(
                needle: $email,
                haystack: $sentEmails,
                message: "Block \"To\" on {$index} step doesn't contain '{$email}'. It only contains '{$emailList}'.",
            );
        }
    }

    protected function assertEmailFrom(array $currentMail, Mailable $mail): void
    {
        $expectedFrom = Arr::get($currentMail, 'from');

        if (!empty($expectedFrom)) {
            $expectedFrom = Arr::wrap($expectedFrom);

            foreach ($expectedFrom as $expected) {
                $expectedJson = json_encode($expected);

                $this->assertTrue(
                    condition: $mail->hasFrom($expected['address'] ?? $expected, $expected['name'] ?? null),
                    message: "Email was not from expected address [{$expectedJson}].",
                );
            }
        }
    }

    protected function assertCountMails(array $emailChain, int $index): void
    {
        $countData = count($emailChain);

        $this->assertEquals(
            expected: $countData,
            actual: $index,
            message: "Failed assert that send emails count are equals, expected send email count: {$countData}, actual {$index}.",
        );
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
        $view = $mail->content()->view;
        $data = array_merge($mail->content()->with, $mail->buildViewData());

        $mailContent = view($view, $data)->render();

        $globalExportMode = $this->globalExportMode ?? false;

        if ($exportMode || $globalExportMode) {
            $this->exportContent($mailContent, $expectedMailData['fixture']);
        }

        $fixture = $this->getFixture($expectedMailData['fixture']);

        $this->assertEquals(
            expected: $fixture,
            actual: $mailContent,
            message: "Fixture {$expectedMailData['fixture']} does not equals rendered mail.",
        );
    }

    protected function prepareEmailChain($emailChain): array
    {
        if (is_string($emailChain)) {
            $emailChain = $this->getJsonFixture($emailChain);
        }

        return (is_multidimensional($emailChain)) ? $emailChain : [$emailChain];
    }

    protected function mockedMail($emails, string $fixture, string $subject = '', $from = '', $attachments = []): array
    {
        return [
            'emails' => $emails,
            'fixture' => $fixture,
            'subject' => $subject,
            'from' => $from,
            'attachments' => $attachments,
        ];
    }

    protected function assertAttachments(array $currentMail, Mailable $mail): void
    {
        $attachments = Arr::get($currentMail, 'attachments', []);
        $className = get_class($mail);

        if (count($attachments)) {
            $this->assertTrue(
                condition: method_exists($mail, 'assertHasAttachment'),
                message: "Class {$className} doesn't have method `assertHasAttachment` to check an attachment.",
            );

            foreach ($attachments as $attachment) {
                $mail->assertHasAttachment($attachment);
            }
        }
    }
}
