<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use RonasIT\Support\Tests\Support\Mock\LegacyTestMail;
use RonasIT\Support\Tests\Support\Mock\TestMail;
use RonasIT\Support\Tests\Support\Mock\TestMailManyFromWithName;
use RonasIT\Support\Tests\Support\Mock\TestMailWithAttachments;

class MailsMockTraitTest extends HelpersTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        Config::set('view.paths', [base_path('tests/support/views')]);

        putenv('MAIL_FROM_ADDRESS=noreply@mail.net');
    }

    public function testMail()
    {
        Mail::to('test@mail.com')->queue(new TestMail(['name' => 'John Smith']));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail.html',
                'subject' => 'Test Subject',
                'from' => 'noreply@mail.net',
            ],
        ]);
    }

    public function testMailWithDifferentQueue()
    {
        $mail = (new TestMail(['name' => 'John Smith']))->onQueue('different_queue');
        Mail::to('test@mail.com')->queue($mail);

        Mail::assertQueued(fn (TestMail $mail) => $mail->queue === 'different_queue');
    }

    public function testLegacyMail()
    {
        Mail::to('test@mail.com')->queue(new LegacyTestMail(
            ['name' => 'John Smith'],
            'Test Subject',
            'emails.test',
        ));

        $this->assertMailEquals(LegacyTestMail::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail.html',
                'subject' => 'Test Subject',
                'from' => 'noreply@mail.net',
            ],
        ]);
    }

    public function testMailFromManyWithName()
    {
        Mail::to('test@mail.com')->queue(new TestMailManyFromWithName(['name' => 'John Smith']));

        $this->assertMailEquals(TestMailManyFromWithName::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail.html',
                'subject' => 'Test Subject',
                'from' => [
                    [
                        'address' => 'noreply@mail.net',
                        'name' => 'Some sender',
                    ],
                    [
                        'address' => 'noreply-second@mail.net',
                        'name' => 'Some sender second case',
                    ],
                    [
                        'address' => 'noreply-withoutsender@mail.net',
                    ],
                    [
                        'address' => 'noreply-withoutsender-second@mail.net',
                        'name' => null,
                    ],
                ],
            ],
        ]);
    }

    public function testMailWhenEmailChainIsJson()
    {
        $mail1 = new TestMail(['name' => 'John Smith']);
        $mail2 = new TestMail(['name' => 'Alex Jameson']);

        Mail::to('test1@mail.com')->queue($mail1->subject('Test Subject1'));
        Mail::to('test2@mail.com')->queue($mail2->subject('Test Subject2'));

        $this->assertMailEquals(TestMail::class, 'email_chain.json');
    }

    public function testMailWithExport()
    {
        putenv('FAIL_EXPORT_JSON=false');

        Mail::to('test@mail.com')->queue(new TestMail(['name' => 'John Smith']));

        $this->assertMailEquals(TestMail::class, [
            $this->mockedMail('test@mail.com', 'test_mail_with_export.html', 'Test Subject'),
        ], true);

        $this->assertFileExists($this->getFixturePath('test_mail_with_export.html'));
    }

    public function testMailWithGlobalExportMode()
    {
        putenv('FAIL_EXPORT_JSON=false');

        Mail::to('test@mail.com')->queue(new TestMail(['name' => 'John Smith']));

        $this->assertMailEquals(TestMail::class, [
            $this->mockedMail('test@mail.com', 'test_mail_with_global_export.html', 'Test Subject'),
        ]);

        $this->assertFileExists($this->getFixturePath('test_mail_with_export.html'));
    }

    public function testMailWithIncorrectSubject()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            'Failed assert that the expected subject "Incorrect Subject" equals to the actual "Test Subject".'
        );

        Mail::to('test@mail.com')->queue(new TestMail(['name' => 'John Smith']));

        $this->assertMailEquals(TestMail::class, [
            $this->mockedMail('test@mail.com', 'test_mail.html', 'Incorrect Subject'),
        ]);
    }

    public function testMailWithoutRequiredParameters()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Missing required key "fixture" in the input data set on the step: 0.');

        Mail::to('test@mail.com')->queue(new TestMail(['name' => 'John Smith']));

        Mail::assertQueued(function (TestMail $mail) {
            return ($mail->queue === 'mails');
        });

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
            ],
        ]);
    }

    public function testMailWithAttachment()
    {
        Mail::to('test@mail.com')->queue(new TestMailWithAttachments(['name' => 'John Smith']));

        $this->assertMailEquals(TestMailWithAttachments::class, [
            $this->mockedMail(
                emails: 'test@mail.com',
                fixture: 'test_mail.html',
                subject: 'Test Subject',
                attachments: [
                    'attachment1',
                    ['file' => new \stdClass(), 'options' => ['some_options']],
                ],
            ),
        ]);
    }
}
