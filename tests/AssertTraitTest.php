<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\ExpectationFailedException;
use RonasIT\Support\Tests\Support\Mock\TestMail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssertTraitTest extends HelpersTestCase
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
        Mail::to('test@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test'
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail.html',
            ]
        ]);
    }

    public function testMailWithAllParameters()
    {
        Mail::to('test@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test',
            'Test Subject',
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail.html',
                'subject' => 'Test Subject',
                'from' => 'noreply@mail.net',
            ]
        ]);
    }

    public function testMailWhenEmailChainIsJson()
    {
        Mail::to('test1@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test',
            'Test Subject1',
        ));
        Mail::to('test2@mail.com')->queue(new TestMail(
            ['name' => 'Alex Jameson'],
            'emails.test',
            'Test Subject2',
        ));

        $this->assertMailEquals(TestMail::class, 'email_chain.json');
    }

    public function testMailWithExport()
    {
        putenv('FAIL_EXPORT_JSON=false');

        Mail::to('test@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test'
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
                'fixture' => 'test_mail_with_export.html',
            ]
        ], true);

        $this->assertFileExists($this->getFixturePath('test_mail_with_export.html'));
    }

    public function testMailWithIncorrectSubject()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            'Failed assert that the expected subject "Incorrect Subject" equals to the actual "Test Subject".'
        );

        Mail::to('test@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test',
            'Test Subject',
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
                'subject' => 'Incorrect Subject',
                'fixture' => 'test_mail.html',
            ]
        ]);
    }

    public function testMailWithoutRequiredParameters()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Missing required key "fixture" in the input data set on the step: 0.');

        Mail::to('test@mail.com')->queue(new TestMail(
            ['name' => 'John Smith'],
            'emails.test'
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'test@mail.com',
            ]
        ]);
    }
}