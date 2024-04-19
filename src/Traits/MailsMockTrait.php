<?php

namespace RonasIT\Support\Traits;

use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

trait MailsMockTrait
{
    use FixturesTrait;

    private array $requiredExpectationParameters = [
        'emails',
        'fixture'
    ];

    /**
     * Email Chain should look like following construction:
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *   ]
     *
     * or be a function call:
     *
     *   $this->mockedMail($emails, $fixture, $subject, $from),
     *
     * or be an array, if sent more than 1 email:
     *
     * [
     *   [
     *      'emails' => string|array, email addresses to which the letter is expected to be sent on the step 1
     *      'fixture' => 'expected_rendered_fixture.html', fixture name to which send email expected to be equal on the step 1
     *      'subject' => string|null, expected email subject from the step 1
     *   ],
     *   ...
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
            $this->assertAttachments($expectedMailData, $mail, $index);

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
            if (method_exists($mail, 'hasSubject')) {
                $subject = method_exists($mail, 'envelope') ? $mail->envelope()->subject : $mail->subject;

                $this->assertTrue(
                    $mail->hasSubject($expectedSubject),
                    "Failed assert that the expected subject \"{$expectedSubject}\" equals "
                    . "to the actual \"{$subject}\"."
                );
            } else {
                $this->assertEquals(
                    $expectedSubject,
                    $mail->subject,
                    "Failed assert that the expected subject \"{$expectedSubject}\" equals "
                    . "to the actual \"{$mail->subject}\"."
                );
            }
        }
    }

    protected function assertAddressesCount(array $emails, Mailable $mail, int $index): void
    {
        $expectedAddressesCount = count($emails);
        $addressesCount = count($mail->to);

        $this->assertEquals(
            $expectedAddressesCount,
            $addressesCount,
            "Failed assert that email on the step {$index}, was sent to {$expectedAddressesCount} addresses, "
            . "actually email had sent to the {$addressesCount} addresses."
        );
    }

    protected function assertSentToEmailsList(array $sentEmails, array $emails, int $index): void
    {
        $emailList = implode(',', $sentEmails);

        foreach ($emails as $email) {
            $this->assertContains(
                $email,
                $sentEmails,
                "Block \"To\" on {$index} step doesn't contain '{$email}'. It only contains '{$emailList}'."
            );
        }
    }

    protected function assertEmailFrom(array $currentMail, Mailable $mail): void
    {
        $expectedFrom = Arr::get($currentMail, 'from');

        if (!empty($expectedFrom)) {
            $this->assertTrue(
                $mail->hasFrom($expectedFrom),
                "Email was not from expected address [{$expectedFrom}]."
            );
        }
    }

    protected function assertCountMails(array $emailChain, int $index): void
    {
        $countData = count($emailChain);

        $this->assertEquals(
            $countData,
            $index,
            "Failed assert that send emails count are equals, expected send email count: {$countData}, actual {$index}."
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
        $view = (method_exists($mail, 'content')) ? $mail->content()->view : $mail->view;
        $mailContent = view($view, $mail->viewData)->render();

        if ($exportMode) {
            $this->exportContent($mailContent, $expectedMailData['fixture']);
        }

        $fixture = $this->getFixture($expectedMailData['fixture']);

        $this->assertEquals(
            $fixture,
            $mailContent,
            "Fixture {$expectedMailData['fixture']} does not equals rendered mail."
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

    protected function assertAttachments(array $currentMail, Mailable $mail, int $index): void
    {
        $expectedAttachments = Arr::get($currentMail, 'attachments');

        if (!empty($expectedAttachments)) {
            $expectedAttachmentsCount = count($expectedAttachments);

            if (method_exists($mail, 'attachments')) {
                $attachmentsCount = count($mail->attachments);

                $this->assertCount(
                    $expectedAttachmentsCount,
                    $mail->attachments,
                    "Mail contains {$attachmentsCount} attachments instead of {$expectedAttachmentsCount}"
                    . " on the step: {$index}."
                );
            } else {
                $this->fail("Mail contains 0 attachments instead of {$expectedAttachmentsCount} on the step: {$index}.");
            }
        }
    }
}